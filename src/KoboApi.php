<?php

namespace KoboSDK;

use GuzzleHttp\Psr7\MultipartStream;
use KoboSDK\Http\Exceptions\HttpException;
use KoboSDK\Http\ResponseHandler;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Ramsey\Uuid\Uuid;
use SimpleXMLElement;

final class KoboApi implements KoboSDKInterface
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
        private readonly StreamFactoryInterface $streamFactory,
        private readonly string $apiUrl,
        private readonly string $apiKey,
        private readonly ?string $apiV1Url
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

    public function getMedia(): array
    {
        $request = $this->requestFactory->createRequest('GET', $this->apiV1Url . '/api/v1/media')
            ->withHeader('Authorization', 'Token ' . $this->apiKey)
            ->withHeader('Accept', 'application/json');

        return $this->sendRequest($request);
    }

    public function submit(string $formId, array $data): array
    {
        $data['meta'] = [
            'instanceID' => 'uuid:' . Uuid::uuid4()->toString(),
        ];

        $requestBody = [
            'id'         => $formId,
            'submission' => $data,
        ];

        $stream = $this->streamFactory->createStream(json_encode($requestBody));

        $request = $this->requestFactory->createRequest('POST', $this->apiV1Url . '/api/v1/submissions')
            ->withBody($stream)
            ->withHeader('Authorization', 'Token ' . $this->apiKey)
            ->withHeader('Content-Type', 'application/json');

        return $this->sendRequest(request: $request, expectedStatus: [201]);
    }

    /**
     * @throws \Exception
     */
    public function submitXml(string $formId, array $data): array
    {
        $form = $this->asset($formId);

        if (isset($form->data['deployment__uuid'])) {
            $xml       = new SimpleXMLElement('<' . $formId . '/>');
            $form_uuid = $form->data['deployment__uuid'];

            $xml->addAttribute('id', $formId);
            $xml->addChild('formhub')->addChild('uuid', $form_uuid);

            $data['meta'] = [
                'instanceID' => 'uuid:' . Uuid::uuid4()->toString(),
            ];

            Utils::arrayToXml($data, $xml);

        } else {
            throw new \Exception('Form does not have deployment UUID, cannot submit XML.');
        }

        $tempFile = Utils::createTempXmlFile($xml->asXML());
        $stream   = $this->createMultipartStream($tempFile);
        // $stream   = $this->streamFactory->createStream($tempFile);

        try {
            $request = $this->requestFactory->createRequest('POST', $this->apiV1Url . '/api/v1/submissions')
                ->withBody($stream)
                ->withHeader('Content-Type', 'multipart/form-data; boundary=' . $stream->getBoundary())
                ->withHeader('Authorization', 'Token ' . $this->apiKey)
                ->withHeader('Accept', 'application/json');

            return $this->sendRequest(request: $request, expectedStatus: [201]);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    private function createMultipartStream(string $filePath): MultipartStream
    {
        $elements = [
            [
                'name'     => 'xml_submission_file',
                'contents' => fopen($filePath, 'r'),
                'filename' => 'submission.xml',
                'headers'  => ['Content-Type' => 'application/xml'],
            ],
        ];

        return new MultipartStream($elements);
    }

    /**
     * Get Full form metadata
     *
     * @return Asset{permissions: array, type: string, content: array, uid: string, name: string>}
     */
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

    /**
     * Get the questions, and answers with labels of a specific form
     *
     * @return array{kind: string, uid_asset: string, data: array<string, mixed>}
     */
    public function assetContent(string $formId): array
    {
        $request = $this->requestFactory->createRequest('GET', $this->apiUrl . '/api/v2/assets/' . $formId . '/content/')
            ->withHeader('Authorization', 'Token ' . $this->apiKey)
            ->withHeader('Accept', 'application/json');

        return $this->sendRequest($request);
    }

    /**
     * Get submissions for a specific form with optional filters
     *
     * @param  $filters  array{start?: \DateTimeImmutable, end?: \DateTimeImmutable, limit?: int, offset?: int}
     * @return array{count: int, next: ?int, previous: ?int, results: array<int, array<string, mixed>>}
     */
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

    public function submissionRaw(string $formId, string $submissionId): array
    {
        $request = $this->requestFactory->createRequest('GET', $this->apiUrl . '/api/v2/assets/' . $formId . '/data/' . $submissionId . '/')
            ->withHeader('Authorization', 'Token ' . $this->apiKey)
            ->withHeader('Accept', 'application/json');

        return $this->sendRequest($request);
    }

    /**
     * Get a single submission by its ID
     *
     * @return array<string, mixed>
     */
    public function submission(string $formId, string $submissionId, array $keepFields = []): array
    {
        $submission = $this->submissionRaw($formId, $submissionId);

        // Remove metadata fields from the submission data
        foreach ($this->metadataFields as $metaField) {
            if (! in_array($metaField, $keepFields)) {
                unset($submission[$metaField]);
            }
        }

        // Flatten any nested fields by keeping only the last part of the field name
        foreach ($submission as $key => $value) {
            $field_name = explode('/', $key);
            if (count($field_name) > 1) {
                $new_key              = end($field_name);
                $submission[$new_key] = $value;
                unset($submission[$key]);
            }
        }

        return $submission;
    }

    /**
     * Get Enketo edit link for a submission
     *
     * @return array{url: string, version_uid: string}
     */
    public function getEditLink(string $formId, string $submissionId): array
    {
        $request = $this->requestFactory->createRequest('GET', $this->apiUrl . '/api/v2/assets/' . $formId . '/data/' . $submissionId . '/enketo/edit/')
            ->withHeader('Authorization', 'Token ' . $this->apiKey)
            ->withHeader('Accept', 'application/json');

        return $this->sendRequest($request);
    }

    /**
     * List attachments for a submission using the xpath for a specific field
     *
     * @return array{content: object, headers: array, content_type: string, status: int}
     */
    public function getSubmissionAttachments(string $formId, string $submissionId, string $path): array
    {
        $request = $this->requestFactory->createRequest('GET', $this->apiUrl . '/api/v2/assets/' . $formId . '/data/' . $submissionId . '/attachments/')
            ->withHeader('Authorization', 'Token ' . $this->apiKey)
            ->withHeader('Accept', '*/*');

        $uri     = $request->getUri();
        $newUri  = $uri->withQuery(http_build_query(['xpath' => $path]));
        $request = $request->withUri($newUri);

        return $this->sendRequest($request, true);
    }

    /**
     * Retrieve a single attachment (binary).
     *
     * @return array{content: object, headers: array, content_type: string, status: int}
     */
    public function getAttachment(string $formId, string $submissionId, string $attachmentId): array
    {
        $request = $this->requestFactory->createRequest('GET', $this->apiUrl . '/api/v2/assets/' . $formId . '/data/' . $submissionId . '/attachments/' . $attachmentId . '/')
            ->withHeader('Authorization', 'Token ' . $this->apiKey)
            ->withHeader('Accept', '*/*');

        return $this->sendRequest($request, true);
    }

    private function sendRequest(RequestInterface $request, bool $attachment = false, array $expectedStatus = [200]): array
    {
        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new HttpException('HTTP client error when sending request: ' . $e->getMessage(), 0, null, $e);
        } catch (\Throwable $e) {
            throw new HttpException('Unexpected error when sending request: ' . $e->getMessage(), 0, null, $e);
        }

        if ($attachment) {
            return ResponseHandler::handleAttachment($response);
        }

        return ResponseHandler::handle($response, $expectedStatus);
    }
}
