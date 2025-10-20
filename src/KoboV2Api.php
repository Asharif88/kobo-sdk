<?php

namespace KoboSDK;

use KoboSDK\Http\Exceptions\HttpException;
use KoboSDK\Http\ResponseHandler;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

final class KoboV2Api implements KoboSDKInterface
{
    public function __construct(
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private string $apiUrl,
        private string $apiKey
    ) {}

    public function getAssets(): array
    {
        $request = $this->requestFactory->createRequest('GET', $this->apiUrl . '/api/v2/assets/')
            ->withHeader('Authorization', 'Token ' . $this->apiKey)
            ->withHeader('Accept', 'application/json');

        return $this->sendRequest($request);
    }

    public function getSubmissions(string $formId, array $filters = []): array
    {
        // TODO: Implement getSubmissions() method.

    }

    private function sendRequest(RequestInterface $request): array
    {
        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new HttpException('HTTP client error when sending request: ' . $e->getMessage(), 0, null, $e);
        } catch (\Throwable $e) {
            throw new HttpException('Unexpected error when sending request: ' . $e->getMessage(), 0, null, $e);
        }

        return ResponseHandler::handle($response);
    }
    //            $url .= '?' . $qs;
    //        }
    //
    //        $request = $this->requestFactory->createRequest('GET', $url);
    //        $request = $request->withHeader('Authorization', 'Token ' . $this->apiKey);
    //        $request = $request->withHeader('Accept', 'application/json');
    //
    //        $response = $this->httpClient->sendRequest($request);
    //
    //        return ResponseHandler::handle($response, $expectedStatusCodes);
    //    }
}
