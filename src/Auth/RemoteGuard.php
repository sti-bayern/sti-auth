<?php

namespace Sti\StiAuth\Auth;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Sti\StiAuth\Services\AuthClient;

class RemoteGuard implements Guard
{
    protected ?Authenticatable $user = null;

    public function __construct(protected RemoteUserProvider $provider, protected Request $request) {}

    public function user(): ?Authenticatable
    {
        if ($this->user) return $this->user;

        $token = $this->request->session()->get('auth_token');
        if (!$token) return null;

        $client = new AuthClient([
            'base_url' => config('lara-auth.base_url'),
            'timeout'  => config('lara-auth.timeout', 3),
            'token'    => config('lara-auth.token'),
        ]);

        $data = $client->validateToken($token);
        if (!$data) return null;

        $this->user = new RemoteUser($data);
        return $this->user;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }
    public function guest(): bool
    {
        return !$this->check();
    }
    public function id(): ?int
    {
        return $this->user()?->getAuthIdentifier();
    }
    public function validate(array $credentials = []): bool
    {
        return false;
    }
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;
        return $this;
    }

    // Laravel 11/12 Interface: neue Methode
    public function hasUser(): bool
    {
        return $this->user !== null;
    }
}
