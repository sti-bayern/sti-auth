<?php

namespace Sti\StiAuth\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLogin()
    {
        return view(config('sti-auth.view'));
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $response = Http::post(config('sti-auth.base_url') . '/api/login', $data);

        if (!$response->ok()) {
            throw ValidationException::withMessages([
                'username' => ['Login fehlgeschlagen.']
            ]);
        }

        $payload = $response->json();

        // Token in Session (RemoteGuard nutzt dieses Token)
        session([
            config('sti-auth.token') => $payload['token'],
        ]);

        // session_regenerate_id
        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    public function logout(Request $request)
    {
        // optional: rufe remote logout (wenn du willst)
        $token = $request->session()->get('auth_token');
        if ($token) {
            Http::withToken($token)->post(config('sti-auth.base_url') . '/api/logout');
        }

        session()->forget('auth_token');

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
