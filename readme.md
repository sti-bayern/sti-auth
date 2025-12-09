# STI Auth -- Zentrales Login über Remote-Laravel-Auth

Ein Laravel-Package zur Anmeldung gegen einen zentralen Auth-Server (z.
B. BVV-Adressverwaltung") über einen Remote-Token-Mechanismus.

Dieses Package ersetzt das lokale Login der Client-Anwendung und nutzt
stattdessen eine externe Laravel-App zur Authentifizierung.\
Die Session des Clients enthält anschließend ein Remote-Token, das
regelmäßig über `/api/validate` geprüft wird.

## Installation

### Package installieren

    composer require sti-bayern/sti-auth

Bei lokaler Entwicklung:

``` json
"repositories": [
    {
        "type": "path",
        "url": "packages/sti-auth"
    }
]
```

    composer require sti/sti-auth:* --dev

## Konfiguration

### Setzen der Env-Variablen

In der `.env` können optional folgende Variablen gesetzt werden: (die Beispielwerte entsprechen den Standardwerten)
    
    AUTH_BASE_URL=https://adressen.ldbv.bybn.de     # Url des Anmeldedienstes
    AUTH_API_VIEW=sti-auth::login                   # View-Name des Login-Templates
    AUTH_API_ROUTE_LOGIN=/login                     # Route-Name des Login-Formulars
    AUTH_API_ROUTE_LOGout=/logout                   # Route-Name des Logout-Formulars
    AUTH_API_TOKEN=auth_token                       # Name des intern verwendeten Session-Tokens

### optional Config veröffentlichen

Optional läßt sich die config auch veröffentlichen. Im Normalfall ist das nicht notwendig, die Konfiguration über die .env-Variablen reicht aus.

    php artisan vendor:publish --provider="Sti\StiAuth\StiAuthServiceProvider" --tag=config

Es entsteht:

    config/sti-auth.php

## Remote Auth Guard einrichten (Laravel 12)

`config/auth.php` öffnen und ergänzen:

``` php
'guards' => [
    'web' => [
        'driver'   => 'remote',
        'provider' => 'remote-users',
    ],
],

'providers' => [
    'remote-users' => [
        'driver' => 'remote',
    ],
],
```

Damit verwendet Laravel automatisch den Remote-Guard.

`Auth::user()` gibt die Infos des angemeldeten Benutzer aus.

## ggf. Routen des Packages anpassen

Standardmäßig läuft das Login über die Route `/login`.

Das kann entweder über die entsprechenden Variablen in der .env-Datei geändert werden, oder man veröffentlich die route-File des Pakets und passt diese an.

    php artisan vendor:publish --provider="Sti\StiAuth\StiAuthServiceProvider" --tag=route

## Optionale Synchronisierung in eine lokale User-Tabelle

Standardmäßig gibt `Auth::user()` einen `RemoteUser` zurück, der nur die vom Authentifizierungsserver bereitgestellten Daten enthält. Es ist jedoch möglich, diese Daten bei jeder Anmeldung automatisch in eine lokale Datenbanktabelle zu synchronisieren. Dies ermöglicht es Ihnen, Beziehungen zu anderen Modellen in Ihrer Anwendung zu definieren und die Benutzerdaten lokal zu erweitern.

### 1. Migration veröffentlichen und ausführen

Zuerst müssen Sie die Migrationsdatei veröffentlichen, die eine Standard-Benutzertabelle erstellt:

    php artisan vendor:publish --provider="Sti\StiAuth\StiAuthServiceProvider" --tag=migrations
    php artisan migrate

### 2. Konfiguration anpassen

Öffnen Sie die Konfigurationsdatei `config/sti-auth.php` (veröffentlichen Sie sie, falls noch nicht geschehen) und passen Sie den Abschnitt `local_user` an.

```php
// config/sti-auth.php

'local_user' => [
    // Geben Sie hier Ihr lokales Benutzermodell an.
    'model' => App\Models\User::class,

    // Der Name der Datenbanktabelle (optional, Standard ist 'users').
    'table' => 'users',

    // Mappen Sie hier die Attribute des Remote-Users auf Ihre lokalen Datenbankspalten.
    'sync_attributes' => [
        'id'    => 'id',
        'name'  => 'name',
        'email' => 'email',
        'login' => 'login',
    ],
],
```

Wenn `local_user.model` auf ein gültiges Eloquent-Modell gesetzt ist, wird die Synchronisierung aktiviert. Bei jeder Anmeldung wird der Benutzer in der lokalen Tabelle über die `id` gesucht und aktualisiert (`updateOrCreate`). `Auth::user()` gibt dann eine Instanz Ihres lokalen Modells anstelle des `RemoteUser` zurück.

Wenn `local_user.model` auf `null` gesetzt ist (Standardeinstellung), ist die Synchronisierung deaktiviert, und das Paket verhält sich wie zuvor.

### 3. Eloquent-Modell erstellen

Das Paket liefert nur die Migration, um die Datenbanktabelle zu erstellen. Für das passende Eloquent-Modell sind Sie selbst verantwortlich. **Es ist zwingend erforderlich, dass Ihr Modell die `Illuminate\Contracts\Auth\Authenticatable`-Schnittstelle implementiert.**

Der einfachste Weg, dies zu erreichen, ist die Erweiterung der Basisklasse `Illuminate\Foundation\Auth\User`, wie im folgenden Beispiel gezeigt. Stellen Sie außerdem sicher, dass die Eigenschaften `$fillable` und `$table` korrekt gesetzt sind.

Hier ist ein Beispiel für ein `App\Models\User`-Modell:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users'; // Muss mit dem Wert in config('sti-auth.local_user.table') übereinstimmen

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'login',
    ];
}
```

**Wichtige Hinweise:**
- Stellen Sie sicher, dass die in `$fillable` aufgeführten Attribute mit den Werten in `config('sti-auth.local_user.sync_attributes')` übereinstimmen.
- Da die `id` vom zentralen Authentifizierungsserver stammt und kein auto-inkrementierender Wert ist, ist es entscheidend, `public $incrementing = false;` im Modell zu setzen.

## Middleware registrieren

Um Routen zu schützen muss zuerst die Middleware registriert werden.

Datei:

    bootstrap/app.php

Erweitern:

``` php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'auth.remote' => \Sti\StiAuth\Middleware\VerifyApiToken::class,
    ]);
})
```

### Geschützte Bereiche

``` php
Route::middleware('auth.remote')->group(function () {
    Route::get('/', fn() => view('dashboard'));
});
```

## Blade-Beispiele

Mit dem Paket funktionieren die Standard-Blade-Authentication-Mechanismen `@auth` und `@guest`

``` blade
@auth
    <p>Hallo, {{ auth()->user()->name }}</p>
@endauth

@guest
    <p>Bitte melden Sie sich an.</p>
@endguest
```

## Interner Ablauf

1.  Benutzer öffnet `/login`
2.  Das Package sendet Credentials an `AUTH_BASE_URL/api/login`
3.  Auth-Server gibt ein Token zurück
4.  Token wird in der Client-Session gespeichert
5.  `auth.remote` Middleware ruft `/api/validate` des Auth-Servers auf
6.  Bei Erfolg: User-Daten werden geliefert
7.  `RemoteGuard` erstellt ein `RemoteUser` Objekt
8.  Laravel erkennt den Benutzer als angemeldet

```
                   +--------------------+
                   |   Personen-App     |
                   |  (Client-System)   |
                   +---------+----------+
                             |
                             | 1. User gibt Username/Passwort ein
                             v
                   +--------------------+
                   |  LoginController   |
                   +---------+----------+
                             |
                             | 2. POST /api/login (Remote Auth)
                             v
+---------------------------------------------------------------+
|                      Zentrale Auth-App                        |
|                          (StiAuth)                            |
|                                                               |
|   +---------------------------+      +---------------------+  |
|   |   AuthController@login    | ---> | Active Directory    |  |
|   | - validiert Credentials   |      | (via ldaprecord)    |  |
|   | - erstellt Token          |      +---------------------+  |
|   +------------+--------------+                               |
|                | 3. Token zurückgeben                         |
+--------------------------------------------------------------+
                             |
                             v
                   +--------------------+
                   |   Personen-App     |
                   +---------+----------+
                             |
                             | 4. Token in Session speichern
                             v
                   +--------------------+
                   |   RemoteGuard      |
                   +---------+----------+
                             |
                             | 5. Abrufen des Tokens aus Session
                             v
+---------------------------------------------------------------+
|                      Zentrale Auth-App                        |
|                          (StiAuth)                            |
|                        /api/validate                          |
|                                                               |
|  +----------------+     Prüfen Token    +-----------------+   |
|  | AuthController | ------------------> | Token gültig?   |   |
|  +----------------+                     +-----------------+   |
|                      6. User-Daten zurückgeben:               |
|                         Auth::user()                          |
+---------------------------------------------------------------+
                             |
                             v
                   +---------------------------+
                   | 7. RemoteUser (Authent.)  |
                   +--------------+------------+
                               |
                               v
                     8. @auth in Blade funktioniert
```

## Support

Bei Fragen, Ideen, Bugreports bitte melden.
