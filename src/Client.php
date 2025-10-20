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

    private string $apiUrl;

    private string $apiKey;

    public function __construct(
        protected array $config,
    ) {
        $this->httpClient     = Psr18ClientDiscovery::find();
        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $this->apiVersion     = $config['api_version'] ?? 'v2';
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

        $this->setApiVersion($this->apiVersion);
    }

    public function setApiVersion(
        string $version
    ): void {
        $this->apiVersion = $version;
        $this->koboSDK    = match ($version) {
            'v2'    => new KoboV2Api($this->httpClient, $this->requestFactory, $this->apiUrl, $this->apiKey),
            default => throw new \InvalidArgumentException('Unsupported API version: ' . $version),
        };
    }

    public function getAssets(): array
    {
        return $this->koboSDK->getAssets();
    }

    public function listAssets()
    {
        $url = $this->apiUrl . '/api/' . $this->apiVersion . '/assets/';

        $request = $this->requestFactory->createRequest('GET', $url)
            ->withHeader('Authorization', 'Token ' . $this->apiKey)
            ->withHeader('Accept', 'application/json');

        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Failed to fetch assets: ' . $response->getStatusCode());
        }

        $body = (string) $response->getBody();

        return json_decode($body, true);
    }
}
