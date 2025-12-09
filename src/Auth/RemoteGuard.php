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

        $token = $this->request->session()->get(config('sti-auth.token'));

        if (!$token) return null;
        // dd([
        //     config('sti-auth'),
        //     config('sti-auth.base_url'),
        //     config('sti-auth.token'),
        //     $this->request->session(config('sti-auth.token')),
        //     $this->request->session()->get(config('sti-auth.token')),
        //     $token
        // ]);

        $client = new AuthClient([
            'base_url' => config('sti-auth.base_url'),
            'timeout'  => config('sti-auth.timeout', 3),
            'token'    => config('sti-auth.token'),
        ]);

        $data = $client->validateToken($token);
        if (!$data) return null;

        // Wenn ein lokales User-Model konfiguriert ist, wird der User synchronisiert
        if ($modelClass = config('sti-auth.local_user.model')) {

            // Sicherstellen, dass das Model die Authenticatable-Schnittstelle implementiert
            if (!in_array(\Illuminate\Contracts\Auth\Authenticatable::class, class_implements($modelClass))) {
                throw new \Exception("The configured local user model [{$modelClass}] must implement the Illuminate\\Contracts\\Auth\\Authenticatable interface.");
            }

            $syncAttributes = config('sti-auth.local_user.sync_attributes', []);
            $localUserData = [];
            foreach ($syncAttributes as $remoteKey => $localKey) {
                if (isset($data[$remoteKey])) {
                    $localUserData[$localKey] = $data[$remoteKey];
                }
            }

            // Sicherstellen, dass die ID für updateOrCreate korrekt gesetzt ist
            $identifierKey = $syncAttributes['id'] ?? 'id';

            if(isset($localUserData[$identifierKey])) {
                $this->user = $modelClass::updateOrCreate(
                    [$identifierKey => $localUserData[$identifierKey]],
                    $localUserData
                );
            }
        } else {
            // Andernfalls wird der RemoteUser wie bisher verwendet
            $this->user = new RemoteUser($data);
        }

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
