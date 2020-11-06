<?php

namespace Netsells\PassportClientCookie\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Application;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UploadedFileFactory;
use Laravel\Passport\Http\Middleware\CheckClientCredentials as LaravelCheckClientCredentials;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

class CheckClientCredentials extends LaravelCheckClientCredentials
{
    /**
     * The Resource Server instance.
     *
     * @var \League\OAuth2\Server\ResourceServer
     */
    protected $server;
    /**
     * @var Encrypter
     */
    private $encrypter;
    /**
     * @var Application
     */
    private $application;

    /**
     * Create a new middleware instance.
     *
     * @param  \League\OAuth2\Server\ResourceServer  $server
     * @return void
     */
    public function __construct(ResourceServer $server, Encrypter $encrypter, Application $application)
    {
        $this->server = $server;
        $this->encrypter = $encrypter;
        $this->application = $application;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$scopes
     * @return mixed
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$scopes)
    {
        if ($request->bearerToken()) {
            $this->authenticateViaBearerToken($request);
        } elseif ($request->cookie(config('passport-client-cookie.cookie_name'))) {
            $this->authenticateViaCookie($request);
        } else {
            if (!$this->shouldSkipMiddleware()) {
                throw new AuthenticationException;
            }
        }

        // Scopes not supported for now
        // $this->validateScopes($psr, $scopes);

        return $next($request);
    }

    private function shouldSkipMiddleware()
    {
        return $this->application->bound('middleware.passport_client_credentials.disable') &&
            $this->application->make('middleware.passport_client_credentials.disable') === true;
    }

    private function authenticateViaBearerToken($request)
    {
        $psr = (new PsrHttpFactory(
            new ServerRequestFactory(),
            new StreamFactory(),
            new UploadedFileFactory(),
            new ResponseFactory()
        ))->createRequest($request);

        try {
            $psr = $this->server->validateAuthenticatedRequest($psr);
            
            app()->instance('oauth.client_id', $psr->getAttribute('oauth_client_id'));
        } catch (OAuthServerException $e) {
            throw new AuthenticationException;
        }
    }

    /**
     * Authenticate the incoming request via the token cookie.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function authenticateViaCookie($request)
    {
        // If we need to retrieve the token from the cookie, it'll be encrypted so we must
        // first decrypt the cookie and then attempt to find the token value within the
        // database. If we can't decrypt the value we'll bail out with a null return.
        try {
            $token = $this->decodeJwtTokenCookie($request);
        } catch (Exception $e) {
            throw new AuthenticationException;
        }

        // We will compare the CSRF token in the decoded API token against the CSRF header
        // sent with the request. If the two don't match then this request is sent from
        // a valid source and we won't authenticate the request for further handling.
        if (! $this->validCsrf($token, $request) ||
            time() >= $token['expiry']) {
            throw new AuthenticationException;
        }

        // If we got this far, we're good!
        if ($token['sub'] !== config('passport-client-cookie.client_id')) {
            throw new AuthenticationException;
        }
    }

    /**
     * Decode and decrypt the JWT token cookie.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function decodeJwtTokenCookie($request)
    {
        return (array) JWT::decode(
            $this->encrypter->decrypt($request->cookie(config('passport-client-cookie.cookie_name', 'laravel_client_token')), false),
            $this->encrypter->getKey(), ['HS256']
        );
    }

    /**
     * Determine if the CSRF / header are valid and match.
     *
     * @param  array  $token
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function validCsrf($token, $request)
    {
        return isset($token['csrf']) && hash_equals(
                $token['csrf'], (string) $request->header('X-CSRF-TOKEN')
            );
    }
}
