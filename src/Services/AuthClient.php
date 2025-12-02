<?php

namespace Sti\StiAuth\Services;

use GuzzleHttp\Client;

class AuthClient
{
    protected Client $http;
    protected string $baseUrl;
    protected string|null $token;

    public function __construct(array $config)
    {
        $this->baseUrl = $config['base_url'];
        $this->token   = $config['token'];

        $this->http = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => $config['timeout'],
        ]);
    }

    public function validateToken(string $token): ?array
    {
        $response = $this->http->post('/api/validate', [
            'json' => ['token' => $token],
            'headers' => [
                'Authorization' => "Bearer {$this->token}"
            ]
        ]);

        return json_decode($response->getBody(), true);
    }
}
