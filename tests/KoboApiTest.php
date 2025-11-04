<?php

namespace KoboSDK\Tests;

use DateTimeImmutable;
// use http\Exception\UnexpectedValueException;
use KoboSDK\Asset;
use KoboSDK\Client;
use KoboSDK\Http\Exceptions\HttpException;
use PHPUnit\Framework\TestCase;

final class KoboApiTest extends TestCase
{
    private $api_url = 'https://eu.kobotoolbox.org';

    private $api_key = '6920790c1c2d39367f85475c9c7cfe872083589d';

    private $url_v1 = 'https://kc-eu.kobotoolbox.org';

    private function createTestClient($v2_only = false): Client
    {
        $config = [
            'url'     => $this->api_url,
            'api_key' => $this->api_key,
        ];

        if (! $v2_only) {
            $config['url_v1'] = $this->url_v1;
        }

        return new Client(config: $config);
    }

    public function test_client_creation_missing_api_key(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $config = [
            'url' => $this->api_url,
            // 'api_key' is missing
        ];

        new Client(config: $config);
    }

    public function test_client_creation_missing_url(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $config = [
            // 'url' is missing
            'api_key' => $this->api_key,
        ];

        new Client(config: $config);
    }

    public function test_get_assets_returns_forms(): void
    {
        $client  = $this->createTestClient(v2_only: false);
        $results = $client->getAssets();

        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        $this->assertArrayHasKey('count', $results);
        $this->assertArrayHasKey('next', $results);
        $this->assertArrayHasKey('previous', $results);
        $this->assertArrayHasKey('results', $results);
    }

    public function test_asset_returns_asset_object(): void
    {
        $client = $this->createTestClient(v2_only: false);
        $asset  = $client->asset(formId: 'arM7332BXdeYcKPj58Vnpx');

        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertEquals('arM7332BXdeYcKPj58Vnpx', $asset->formId);
        $this->assertObjectHasProperty('data', $asset);
        $this->assertObjectHasProperty('content', $asset);
        $this->assertObjectHasProperty('type', $asset);
        $this->assertIsArray($asset->data);
        $this->assertIsArray($asset->content);
        $this->assertEquals('survey', $asset->type);
    }

    public function test_get_media_exception_missing_v1_url(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $client = $this->createTestClient(v2_only: true);

        $client->getMedia();
    }

    public function test_get_submission_attachment(): void
    {
        $client     = $this->createTestClient(v2_only: false);
        $attachment = $client->getSubmissionAttachments(
            formId: 'arM7332BXdeYcKPj58Vnpx',
            submissionId: '719704671',
            path: 'attachment'
        );

        $this->assertNotEmpty($attachment);
        $this->assertIsArray($attachment);
        $this->assertEquals(200, $attachment['status']);
    }

    public function test_get_attachment(): void
    {
        $client     = $this->createTestClient(v2_only: false);
        $attachment = $client->getAttachment(
            formId: 'arM7332BXdeYcKPj58Vnpx',
            submissionId: '719704671',
            attachmentId: 'attsm4fBZCpeyH7XVbSxqDe2'
        );

        $this->assertNotEmpty($attachment);
        $this->assertIsArray($attachment);
        $this->assertEquals(200, $attachment['status']);
    }

    public function test_submit_json(): void
    {
        $client          = $this->createTestClient(v2_only: false);
        $formId          = 'arM7332BXdeYcKPj58Vnpx';
        $date            = new DateTimeImmutable;
        $submissionArray = [
            'start'             => $date->format('c'),
            'end'               => $date->format('c'),
            'name'              => 'anchal test ' . $date->format('c'),
            'are_you_available' => 'OK',
            'country'           => 'germany france',
            'group_fields'      => [
                'notest' => 'This is a note',
            ],
        ];

        $response = $client->submit($formId, $submissionArray);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('Successful submission.', $response['message']);
        $this->assertArrayHasKey('submissionDate', $response);
    }

    public function test_submit_xml(): void
    {
        $client          = $this->createTestClient(v2_only: false);
        $formId          = 'arM7332BXdeYcKPj58Vnpx';
        $date            = new DateTimeImmutable;
        $submissionArray = [
            'start'             => $date->format('c'),
            'end'               => $date->format('c'),
            'name'              => 'anchal test ' . $date->format('c'),
            'are_you_available' => 'OK',
            'country'           => 'germany france',
            'group_fields'      => [
                'notest' => 'This is a note',
            ],
        ];

        $response = $client->submitXml($formId, $submissionArray);

        $this->assertIsArray($response);
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('Successful submission.', $response['message']);
        $this->assertArrayHasKey('submissionDate', $response);
    }

    public function test_invalid_form_submit_xml()
    {
        $this->expectException(HttpException::class);
        $client          = $this->createTestClient(v2_only: false);
        $formId          = 'invalidFormId';
        $date            = new DateTimeImmutable;
        $submissionArray = [
            'start'             => $date->format('c'),
            'end'               => $date->format('c'),
            'name'              => 'anchal test ' . $date->format('c'),
            'are_you_available' => 'OK',
            'country'           => 'germany france',
            'group_fields'      => [
                'notest' => 'This is a note',
            ],
        ];

        $response = $client->submitXml($formId, $submissionArray);
    }
}
