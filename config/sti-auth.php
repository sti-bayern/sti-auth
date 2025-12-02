<?php

return [
    'base_url' => env('AUTH_API_URL', 'https://adressen.ldbv.bybn.de'),
    'token'    => env('AUTH_API_TOKEN', 'auth_token'),
    'view'    => env('AUTH_API_VIEW', 'sti-auth::login'),
    'route_login' => env('AUTH_API_ROUTE_LOGIN', '/login'),
    'route_logout' => env('AUTH_API_ROUTE_LOGOUT', '/logout'),
    'timeout'  => 3,
];
