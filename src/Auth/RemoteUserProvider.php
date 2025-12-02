<?php

namespace Sti\StiAuth\Auth;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class RemoteUserProvider implements UserProvider
{
    public function retrieveById($identifier)
    {
        return null;
    }
    public function retrieveByToken($identifier, $token)
    {
        return null;
    }
    public function updateRememberToken(Authenticatable $user, $token) {}
    public function retrieveByCredentials(array $credentials)
    {
        return null;
    }
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return false;
    }

    // Laravel 11/12 kompatibel

    // public function rehashPasswordIfRequired(Authenticatable $user, #[\SensitiveParameter] array $credentials, bool $force = false);
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): bool
    {
        // wir nutzen keine lokalen Passwörter → kein Rehash nötig
        // Daher immer 'false' zurückgeben.
        return false;
    }
}
