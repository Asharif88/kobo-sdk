<?php

namespace KoboSDK;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;

class Client
{
    private KoboSDKInterface $koboSDK;

    private string $apiVersion = 'v2';

    private $httpClient;

    private $requestFactory;

    private $streamFactory;

    private string $apiUrl;

    private $apiV1Url;

    private string $apiKey;

    public function __construct(
        protected array $config,
    ) {
        $this->httpClient     = Psr18ClientDiscovery::find();
        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory  = Psr17FactoryDiscovery::findStreamFactory();
        $this->apiV1Url       = $config['url_v1'] ?? null;
        if (! isset($config['url'])) {
            throw new \InvalidArgumentException('Configuration must include a URL.');
        } else {
            $this->apiUrl = rtrim($config['url'], '/');
        }

        if (! isset($config['api_key'])) {
            throw new \InvalidArgumentException('Configuration must include an API key.');
        } else {
            $this->apiKey = $config['api_key'];
        }

        $this->koboSDK = new KoboApi($this->httpClient, $this->requestFactory, $this->streamFactory, $this->apiUrl, $this->apiKey, $this->apiV1Url);
    }

    public function getAssets(int $limit = 100, int $offset = 0, string $asset_type = 'survey'): array
    {
        return $this->koboSDK->getAssets($limit, $offset, $asset_type);
    }

    public function asset(string $formId): Asset
    {
        return $this->koboSDK->asset($formId);
    }

    public function assetContent(string $formId): array
    {
        return $this->koboSDK->assetContent($formId);
    }

    public function getSubmissions(string $formId, array $filters = []): array
    {
        return $this->koboSDK->getSubmissions($formId, $filters);
    }

    public function submissionRaw(string $formId, string $submissionId): array
    {
        return $this->koboSDK->submissionRaw($formId, $submissionId);
    }

    public function submission(string $formId, string $submissionId, array $keepFields = []): array
    {
        return $this->koboSDK->submission($formId, $submissionId, $keepFields);
    }

    public function getEditLink(string $formId, string $submissionId): array
    {
        return $this->koboSDK->getEditLink($formId, $submissionId);
    }

    public function getSubmissionAttachments(string $formId, string $submissionId, string $path): array
    {
        return $this->koboSDK->getSubmissionAttachments($formId, $submissionId, $path);
    }

    public function getAttachment(string $formId, string $submissionId, string $attachmentId): array
    {
        return $this->koboSDK->getAttachment($formId, $submissionId, $attachmentId);
    }

    public function getMedia(): array
    {
        if (is_null($this->apiV1Url)) {
            throw new \InvalidArgumentException('Configuration must include a URL for API v1 to use getMedia().');
        }

        return $this->koboSDK->getMedia();
    }

    public function submit(string $formId, array $data): array
    {
        if (is_null($this->apiV1Url)) {
            throw new \InvalidArgumentException('Configuration must include a URL for API v1 to use submit().');
        }

        return $this->koboSDK->submit($formId, $data);
    }

    public function submitXml(string $formId, array $data): array
    {
        if (is_null($this->apiV1Url)) {
            throw new \InvalidArgumentException('Configuration must include a URL for API v1 to use submit().');
        }

        return $this->koboSDK->submitXml($formId, $data);
    }
}
