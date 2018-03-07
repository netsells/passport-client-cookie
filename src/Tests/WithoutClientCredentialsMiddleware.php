<?php

namespace Netsells\PassportClientCookie\Tests;

class WithoutClientCredentialsMiddleware
{

    /**
     * Disables the middleware checking for client credentials
     */
    public function withoutClientCredentialsMiddleware()
    {
        $this->app->instance('middleware.passport_client_credentials.disable', true);
    }

}