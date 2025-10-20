<?php

namespace KoboSDK\Http\Exceptions;

class UnauthorizedException extends HttpException
{
    public function __construct(string $message = 'Unauthorized', ?string $responseBody = null)
    {
        parent::__construct($message, 401, $responseBody);
    }
}
