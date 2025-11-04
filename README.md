# Kobo SDK — PHP client for KoboToolbox-style APIs

[![PHP](https://img.shields.io/badge/php-8.3-blue.svg)](https://www.php.net/) [![License](https://img.shields.io/badge/license-MIT-lightgrey.svg)](LICENSE)

Lightweight PHP SDK to interact with [KoboToolbox](https://www.kobotoolbox.org/) API.
Simplifies integration with Kobo deployments by wrapping common API endpoints and handling request/response formatting.

Table of contents

- [Overview](#overview)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
  - [Initialization](#initialization)
  - [Fetching Data](#fetching-data)
  - [Submitting Responses](#submitting-responses)
    - [Submit JSON](#submit-json)
    - [Submit XML](#submit-xml)
- [Implementation notes](#implementation-notes)
- [Handling file (binary) responses](#handling-file-binary-responses)
- [Utilities](#utilities)
- [Tests](#tests)
- [Contributing](#contributing)
- [License](#license)


### Overview

This SDK provides a small, focused wrapper around common KoboToolbox API endpoints (v2). It includes helpers to:

- List and inspect assets (forms)
- Retrieve form content and submissions
- Submit new submissions as JSON or XML
- Download submission attachments (images, media)

The library prefers PSR-7/PSR-17 factories for creating requests and streams and a PSR-18 HTTP client for sending requests.

### Requirements

- PHP 8.3
- Composer
- A PSR-18 compatible HTTP client (the project uses discovery by default)
- PSR-17 request and stream factories

### Installation

Install via Composer:

```bash
composer require asharif88/kobo-sdk
```

(For development or to run tests, install dev dependencies as listed in `composer.json`.)

### Configuration

The client expects a configuration array with at least the `url` and `api_key` values. Optionally provide `url_v1` when using endpoints that require the v1 API (media/submissions):

```php
$config = [
    'url'     => 'https://kc.example.org',   // API v2 base URL
    'api_key' => 'YOUR_API_TOKEN',
    'url_v1'  => 'https://kc-v1.example.org' // optional, required for some endpoints
];
```

### Usage

#### Initialization

Create a client instance and use the main helpers:

```php
use KoboSDK\Client;

$config = [
    'url'     => 'https://kc.example.org',
    'api_key' => 'YOUR_API_KEY',
    'url_v1'  => 'https://kc-v1.example.org',
];

$client = new Client(config: $config);
```

#### Fetching Data

```php
// List assets (forms)
$assets = $client->getAssets();

// Get full form metadata
// This function return an Asset object with permissions, type, content, uid & name properties
$form  = $client->asset('FORM_UID');

// Get the questions, and answers with labels of a specific form
$form = $client->assetContent('FORM_UID');

// Get all submissions of a specific form
$subs  = $client->getSubmissions('FORM_UID');

// getSubmissions supports filtering by date & pagination:
$subs  = $client->getSubmissions('FORM_UID', [
    'start' => new DateTimeImmutable('2024-01-01T00:00:00'),
    'end'   => new DateTimeImmutable('2024-01-31T23:59:59'),
    'offset'     => 0,
    'limit'     => 50,
]);

```

```php
// You can get a specific submission with metadata fields removed
$sub   = $client->submission('FORM_UID', 'SUBMISSION_ID');

// or get the full submission with metadata using submissionRaw()
$sub   = $client->submissionRaw('FORM_UID', 'SUBMISSION_ID');

// Get Enketo Link
$link  = $client->getEditLink('FORM_UID', 'SUBMISSION_ID');

// You can also get a list of all attachments you have access to (requires v1 URL)
$attachments = $client->getMedia();
```
#### Submitting Responses
##### Submit JSON

```php
$data = [
    'field1' => 'value',
    'field2' => 'value',
    'nested' => [
        'subfield1' => 'value'
        'subfield2' => 'multiple values'
    ]
];

$response = $client->submit('FORM_UID', $data);
```

##### Submit XML

*recommended*

```php
$data = [
    'field1' => 'value',
    'field2' => 'value',
    'nested' => [
        'subfield1' => 'value'
        'subfield2' => 'multiple values'
    ]
];

$response = $client->submitXml('FORM_UID', $data);
```

### Implementation notes

- v1 URLs are required for some endpoints (media, submissions). The SDK allows specifying a separate `url_v1` in the config.
- Implements the `/v1/submission.xml` endpoint, in both JSON and XML.

> [!NOTE]
> XML submissions are preferred JSON submissions might cause issues with Kobo deployments that expect certain metadata or structure.


- Formats the request body to match Kobo's complex nested structure.
- When submitting XML the SDK will load the form asset to find a `deployment__uuid` which is required by many Kobo deployments. If the form has no deployment UUID the SDK will throw an exception.
- The library ensures `meta.instanceID` exists and will generate a `uuid:...` instance ID if missing.

### Handling file (binary) responses

When the API returns binary data (for example a JPG attachment) you should treat the response body as a stream and persist it rather than trying to JSON-decode it. The SDK exposes attachment handling which returns the PSR-7 stream plus headers and content type.

Example approach:

```php
$attachment = $client->getAttachment('FORM_UID', 'SUBMISSION_ID', 'ATTACHMENT_ID');

// $attachment['content'] is a PSR-7 stream; save to disk:
$stream = $attachment['content'];
$target = fopen('/tmp/photo.jpg', 'wb');
$stream->rewind();
while (! $stream->eof()) {
    fwrite($target, $stream->read(8192));
}
fclose($target);
```

You can also get the attachment by its XPath:

```php
$attachment = $client->getSubmissionAttachments('FORM_UID', 'SUBMISSION_ID', 'group/photo');
```

### Utilities

The `Utils` class contains helpful functions used across the SDK:

- `Utils::arrayToXml(array $data, SimpleXMLElement &$xml)` — convert PHP arrays to XML nodes
- `Utils::createTempXmlFile(string $xml)` — write XML to a temp file (used when building multipart bodies)
- `Utils::formatPermissions(array $permissions)` — normalize permission structures returned by the API

### Tests

The project includes PHPUnit tests. Run them with:

```bash
composer test
# or directly
vendor/bin/phpunit
```

### Contributing

Contributions, bug reports and pull requests are welcome. Feel free to submit a PR or open an issue.

### License

This project is licensed under the MIT License — see the `LICENSE` file for details.

