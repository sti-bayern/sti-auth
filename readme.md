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

In der `.env` können folgende Variablen gesetzt werden. Die Beispielwerte entsprechen den Standardwerten
    
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

    php artisan vendor:publish --provider="Sti\StiAuth\StiAuthServiceProvider" --tag=config

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
+--------------------------------------------------------------+
|                      Zentrale Auth-App                       |
|                          (LaraAuth)                          |
|                                                              |
|   +---------------------------+      +---------------------+  |
|   |   AuthController@login    | ---> | Active Directory    |  |
|   | - validiert Credentials   | 3.   | (via ldaprecord)    |  |
|   | - erstellt Token          |      +---------------------+  |
|   +------------+--------------+                               |
|                | 4. Token zurückgeben                         |
+--------------------------------------------------------------+
                             |
                             v
                   +--------------------+
                   |   Personen-App     |
                   +---------+----------+
                             |
                             | 5. Token in Session speichern
                             v
                   +--------------------+
                   |   RemoteGuard      |
                   +---------+----------+
                             |
                             | 6. Abrufen des Tokens aus Session
                             v
+--------------------------------------------------------------+
|                      Zentrale Auth-App                       |
|                           /api/validate                      |
|                                                              |
|  +----------------+   7. Prüfen Token  +------------------+  |
|  | AuthController | ------------------> | Token gültig?   |  |
|  +----------------+                     +------------------+  |
|                      8. User-Daten zurückgeben               |
+--------------------------------------------------------------+
                             |
                             v
                   +------------------------+
                   | RemoteUser (Authent.)  |
                   +-----------+------------+
                               |
                               v
                     @auth in Blade funktioniert
```

## Support

Bei Fragen, Ideen, Bugreports bitte melden.
