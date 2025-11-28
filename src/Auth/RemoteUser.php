<?php

namespace Sti\StiAuth\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

class RemoteUser implements Authenticatable
{
    protected array $attributes;

    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public function getAuthIdentifierName()
    {
        return 'id';
    }
    public function getAuthIdentifier()
    {
        return $this->attributes['id'] ?? null;
    }
    public function getAuthPassword()
    {
        return null;
    }
    public function getAuthPasswordName()
    {
        return null;
    } // Laravel 11/12 kompatibel
    public function getRememberToken() {}
    public function setRememberToken($value) {}
    public function getRememberTokenName() {}
    public function toArray()
    {
        return $this->attributes;
    }

    public function __get($key)
    {
        return $this->attributes[$key] ?? null;
    }
}
