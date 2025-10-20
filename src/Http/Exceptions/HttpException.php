<?php

namespace KoboSDK\Http\Exceptions;

use RuntimeException;

class HttpException extends RuntimeException
{
    private int $statusCode;

    private ?string $responseBody;

    /**
     * @param  int  $statusCode  HTTP status code for the response (also used as exception code)
     * @param  string|null  $responseBody  Raw response body if available
     * @param  \Throwable|null  $previous  Previous exception for chaining
     */
    public function __construct(string $message, int $statusCode = 0, ?string $responseBody = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode   = $statusCode;
        $this->responseBody = $responseBody;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }
}
