<?php

namespace KoboSDK\Http;

use KoboSDK\Http\Exceptions\HttpException;
use KoboSDK\Http\Exceptions\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;

final class ResponseHandler
{
    /**
     * Validate response status and return decoded JSON body as associative array.
     *
     * @param  int[]  $expectedStatusCodes
     *
     * @throws UnauthorizedException
     * @throws HttpException
     */
    public static function handle(ResponseInterface $response, array $expectedStatusCodes = [200]): array
    {
        self::ensureStatus($response, $expectedStatusCodes);

        return self::parseJson($response);
    }

    /**
     * Validate response status and return raw response content and metadata.
     * Useful for binary payloads like images or attachments.
     *
     * @param  int[]  $expectedStatusCodes
     *
     * @throws UnauthorizedException
     * @throws HttpException
     */
    public static function handleAttachment(ResponseInterface $response, array $expectedStatusCodes = [200]): array
    {
        self::ensureStatus($response, $expectedStatusCodes);

        $body        = $response->getBody();
        $headers     = method_exists($response, 'getHeaders') ? $response->getHeaders() : [];
        $contentType = $response->getHeaderLine('Content-Type');

        return [
            'content'      => $body,
            'headers'      => $headers,
            'content_type' => $contentType === '' ? null : $contentType,
            'status'       => $response->getStatusCode(),
        ];
    }

    private static function ensureStatus(ResponseInterface $response, array $expectedStatusCodes): void
    {
        $status = $response->getStatusCode();
        $body   = (string) $response->getBody();

        if ($status === 401) {
            throw new UnauthorizedException($response->getReasonPhrase() ?: 'Unauthorized', $body ?: null);
        }

        if (! in_array($status, $expectedStatusCodes, true)) {
            $message = $status . ' ' . $response->getReasonPhrase();
            throw new HttpException($message, $status, $body ?: null);
        }
    }

    private static function parseJson(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();

        if ($body === '' || $body === null) {
            return [];
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpException('Failed to decode JSON response: ' . json_last_error_msg(), $response->getStatusCode(), $body);
        }

        return is_array($data) ? $data : [];
    }
}
