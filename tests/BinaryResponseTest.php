<?php

namespace KoboSDK\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class BinaryResponseTest extends TestCase
{
    public function test_handle_raw_returns_binary_content_and_headers(): void
    {
        $binaryData = hex2bin('89504e470d0a1a0a0000000d49484452') . random_bytes(100);

        // Create StreamInterface mock
        $stream = $this->createMock(StreamInterface::class);

        // Set up method expectations
        $stream->method('getContents')->willReturn($binaryData);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($stream);
        $response->method('getReasonPhrase')->willReturn('OK');
        $response->method('getHeaderLine')->willReturn('image/jpeg');
        $response->method('getHeaders')->willReturn(['Content-Type' => ['image/jpeg']]);

        $result = \KoboSDK\Http\ResponseHandler::handleAttachment($response);

        $this->assertArrayHasKey('content', $result);
        $this->assertSame($binaryData, $result['content']->getContents());
        $this->assertSame('image/jpeg', $result['content_type']);
        $this->assertArrayHasKey('headers', $result);
    }
}
