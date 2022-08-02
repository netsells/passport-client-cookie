<?php

namespace Netsells\PassportClientCookie\Middleware;

use Carbon\Carbon;
use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Config\Repository as Config;

class ApiTokenCookieFactory
{
    /**
     * The configuration repository implementation.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * The encrypter implementation.
     *
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    protected $encrypter;

    /**
     * Create an API token cookie factory instance.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @param  \Illuminate\Contracts\Encryption\Encrypter  $encrypter
     * @return void
     */
    public function __construct(Config $config, Encrypter $encrypter)
    {
        $this->config = $config;
        $this->encrypter = $encrypter;
    }

    /**
     * Create a new API token cookie.
     *
     * @param  mixed  $clientId
     * @param  string  $csrfToken
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    public function make($clientId, $csrfToken)
    {
        $config = $this->config->get('session');

        $expiration = Carbon::now()->addMinutes($config['lifetime']);

        return new Cookie(
            config('passport-client-cookie.cookie_name', 'laravel_client_token'),
            $this->createToken($clientId, $csrfToken, $expiration),
            $expiration,
            $config['path'],
            $config['domain'],
            $config['secure'],
            true
        );
    }

    /**
     * Create a new JWT token for the given user ID and CSRF token.
     *
     * @param  mixed  $clientId
     * @param  string  $csrfToken
     * @param  \Carbon\Carbon  $expiration
     * @return string
     */
    protected function createToken($clientId, $csrfToken, Carbon $expiration)
    {
        return JWT::encode(
            [
                'sub' => $clientId,
                'csrf' => $csrfToken,
                'expiry' => $expiration->getTimestamp(),
            ],
            $this->encrypter->getKey(),
            'HS256' //Default algorithm before laravel/passport v10
        );
    }
}
