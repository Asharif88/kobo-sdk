<?php

namespace KoboSDK\Tests;

use KoboSDK\Utils;
use PHPUnit\Framework\TestCase;

final class UtilsTest extends TestCase
{
    public function test_format_date_filter_requires_at_least_one_date(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Utils::formatDateFilter();
    }

    public function test_format_date_filter_produces_gte_and_lte(): void
    {
        $start = new \DateTimeImmutable('2020-01-01T00:00:00+00:00');
        $end   = new \DateTimeImmutable('2020-01-02T00:00:00+00:00');

        $result = Utils::formatDateFilter($start, $end);

        $this->assertArrayHasKey('_submission_time', $result);
        $this->assertArrayHasKey('$gte', $result['_submission_time']);
        $this->assertArrayHasKey('$lte', $result['_submission_time']);
        $this->assertStringContainsString('2020-01-01', $result['_submission_time']['$gte']);
        $this->assertStringContainsString('2020-01-02', $result['_submission_time']['$lte']);
    }

    public function test_format_asset_request_query_defaults(): void
    {
        $q = Utils::formatAssetRequestQuery();

        $this->assertEquals(100, $q['limit']);
        $this->assertEquals(0, $q['offset']);
        $this->assertStringContainsString('asset_type:survey', $q['q']);
    }

    public function test_format_submission_request_query_builds_query_and_pagination(): void
    {
        $start = new \DateTimeImmutable('2021-01-01T00:00:00+00:00');
        $end   = new \DateTimeImmutable('2021-01-02T00:00:00+00:00');

        $filters = ['start' => $start, 'end' => $end, 'limit' => 10, 'offset' => 5];

        $q = Utils::formatSubmissionRequestQuery($filters);

        $this->assertArrayHasKey('query', $q);
        $this->assertArrayHasKey('limit', $q);
        $this->assertArrayHasKey('start', $q);
        $this->assertEquals(10, $q['limit']);
        $this->assertEquals(5, $q['start']);

        $decoded = json_decode($q['query'], true);
        $this->assertArrayHasKey('_submission_time', $decoded);
    }

    public function test_format_permissions(): void
    {
        $permissions = [
            [
                'url'        => 'https://eu.kobotoolbox.org/api/v2/assets/arM7332BXdeYcKPj58Vnpx/permission-assignments/p9KpurTA8DtQEd7XEcbGnm/',
                'user'       => 'https://eu.kobotoolbox.org/api/v2/users/AnonymousUser/',
                'permission' => 'https://eu.kobotoolbox.org/api/v2/permissions/add_submissions/',
                'label'      => 'Add submissions',
            ], [
                'url'        => 'https://eu.kobotoolbox.org/api/v2/assets/arM7332BXdeYcKPj58Vnpx/permission-assignments/pk2az92tarYcjrAM9Pp8kh/',
                'user'       => 'https://eu.kobotoolbox.org/api/v2/users/kobosdktest/',
                'permission' => 'https://eu.kobotoolbox.org/api/v2/permissions/add_submissions/',
                'label'      => 'Add submissions',
            ],
            [
                'url'        => 'https://eu.kobotoolbox.org/api/v2/assets/arM7332BXdeYcKPj58Vnpx/permission-assignments/pk2az92tarYcjrAM9Pp8kh/',
                'user'       => 'https://eu.kobotoolbox.org/api/v2/users/kobosdktest/',
                'permission' => 'https://eu.kobotoolbox.org/api/v2/permissions/change_asset/',
                'label'      => 'Edit form',
            ],
        ];

        $formatted = Utils::formatPermissions($permissions);

        $this->assertArrayHasKey('kobosdktest', $formatted);
        $this->assertArrayHasKey('AnonymousUser', $formatted);
        $this->assertEquals(['add_submissions', 'change_asset'], $formatted['kobosdktest']);
        $this->assertEquals(['add_submissions'], $formatted['AnonymousUser']);
    }

    public function test_array_to_xml_handles_nested_arrays(): void
    {
        $data = ['person' => ['name' => 'John', 'age' => 30]];
        $xml  = new \SimpleXMLElement('<root/>');

        Utils::arrayToXml($data, $xml);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);
        $this->assertStringContainsString('<person><name>John</name><age>30</age></person>', $xml->asXML());
    }

    public function test_array_to_xml_handles_numeric_keys(): void
    {
        $data = ['item1', 'item2'];
        $xml  = new \SimpleXMLElement('<root/>');

        Utils::arrayToXml($data, $xml);

        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);
        $this->assertStringContainsString('<item0>item1</item0>', $xml->asXML());
        $this->assertStringContainsString('<item1>item2</item1>', $xml->asXML());
    }

    public function test_array_to_xml_escapes_special_characters(): void
    {
        $data = ['name' => 'John & Jane'];
        $xml  = new \SimpleXMLElement('<root/>');

        Utils::arrayToXml($data, $xml);

        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);
        $this->assertStringContainsString('<name>John &amp; Jane</name>', $xml->asXML());
    }

    public function test_array_to_xml_handles_empty_array(): void
    {
        $data = [];
        $xml  = new \SimpleXMLElement('<root/>');

        Utils::arrayToXml($data, $xml);

        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);
        $this->assertStringContainsString('<root/>', $xml->asXML());
    }
}
