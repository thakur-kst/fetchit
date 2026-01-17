<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Method
    |--------------------------------------------------------------------------
    |
    | This configuration determines which authentication method to use
    | for user login. FetchIt uses JWT authentication with Google OAuth.
    |
    | - 'jwt': Uses Laravel JWT (tymon/jwt-auth) for authentication
    |
    */
    'method' => env('AUTH_METHOD', 'jwt'), // 'jwt' for FetchIt

    /*
    |--------------------------------------------------------------------------
    | Token Expiration
    |--------------------------------------------------------------------------
    |
    | Token expiration settings for JWT authentication.
    |
    */
    'ttl' => env('JWT_TTL', 60), // Access token expiration (in minutes)

    /*
    |--------------------------------------------------------------------------
    | Refresh Token Expiration
    |--------------------------------------------------------------------------
    |
    | Refresh token expiration (in minutes).
    |
    */
    'refresh_ttl' => env('JWT_REFRESH_TTL', 20160), // Refresh token expiration (in minutes) - 14 days

    /*
    |--------------------------------------------------------------------------
    | Token Hash Algorithm
    |--------------------------------------------------------------------------
    |
    | Algorithm used for hashing tokens.
    |
    */
    'token_hash_algorithm' => env('TOKEN_HASH_ALGORITHM', 'sha256'),

    /*
    |--------------------------------------------------------------------------
    | Login Token Salt
    |--------------------------------------------------------------------------
    |
    | Salt used for login token generation (if needed).
    |
    */
    'login_token_salt' => env('LOGIN_TOKEN_SALT', ''),
];
