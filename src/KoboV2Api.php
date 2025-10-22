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
    public array $metadataFields = [
        'formhub/uuid',
        'meta/instanceID',
        'meta/rootUuid',
        '_id',
        '__version__',
        '_xform_id_string',
        '_uuid',
        '_attachments',
        '_status',
        '_geolocation',
        '_submission_time',
        '_tags',
        '_notes',
        '_validation_status',
        '_submitted_by',
    ];

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly string $apiUrl,
        private readonly string $apiKey
    ) {}

    public function getAssets(int $limit = 100, int $offset = 0, string $asset_type = 'survey'): array
    {
        // Limiting to surveys by default, might add other asset types later if requested
        $request = $this->requestFactory->createRequest('GET', $this->apiUrl . '/api/v2/assets/')
            ->withHeader('Authorization', 'Token ' . $this->apiKey)
            ->withHeader('Accept', 'application/json');

        $queryArray = Utils::formatAssetRequestQuery(limit: $limit, offset: $offset, asset_type: $asset_type);
        $uri        = $request->getUri();
        $newUri     = $uri->withQuery(http_build_query($queryArray));
        $request    = $request->withUri($newUri);

        return $this->sendRequest($request);
    }

    public function asset(string $formId): Asset
    {
        $request = $this->requestFactory->createRequest('GET', $this->apiUrl . '/api/v2/assets/' . $formId . '/')
            ->withHeader('Authorization', 'Token ' . $this->apiKey)
            ->withHeader('Accept', 'application/json');

        $response = $this->sendRequest($request);

        return new Asset(
            formId: $formId,
            data: $response,
        );
    }

    public function assetContent(string $formId): array
    {
        $request = $this->requestFactory->createRequest('GET', $this->apiUrl . '/api/v2/assets/' . $formId . '/content/')
            ->withHeader('Authorization', 'Token ' . $this->apiKey)
            ->withHeader('Accept', 'application/json');

        return $this->sendRequest($request);
    }

    public function getSubmissions(string $formId, array $filters = []): array
    {
        $queryArray = Utils::formatSubmissionRequestQuery($filters);
        $request    = $this->requestFactory->createRequest('GET', $this->apiUrl . '/api/v2/assets/' . $formId . '/data/')
            ->withHeader('Authorization', 'Token ' . $this->apiKey)
            ->withHeader('Accept', 'application/json');

        $uri     = $request->getUri();
        $newUri  = $uri->withQuery(http_build_query($queryArray));
        $request = $request->withUri($newUri);

        return $this->sendRequest($request);
    }

    public function submission(string $formId, string $submissionId): array
    {
        $request = $this->requestFactory->createRequest('GET', $this->apiUrl . '/api/v2/assets/' . $formId . '/data/' . $submissionId . '/')
            ->withHeader('Authorization', 'Token ' . $this->apiKey)
            ->withHeader('Accept', 'application/json');

        return $this->sendRequest($request);
    }

    public function getEditLink(string $formId, string $submissionId): array
    {
        $request = $this->requestFactory->createRequest('GET', $this->apiUrl . '/api/v2/assets/' . $formId . '/data/' . $submissionId . '/enketo/edit/')
            ->withHeader('Authorization', 'Token ' . $this->apiKey)
            ->withHeader('Accept', 'application/json');

        return $this->sendRequest($request);
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
}
