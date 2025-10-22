<?php

namespace KoboSDK;

use DateTimeImmutable;
use DateTimeInterface;

final class Utils
{
    public static function formatDateFilter(?DateTimeImmutable $start = null, ?DateTimeImmutable $end = null): array
    {
        if ($start === null && $end === null) {
            throw new \InvalidArgumentException('Start and end date must not be empty');
        }

        if ($start !== null) {
            $startStr          = $start->format(DateTimeInterface::ATOM);
            $dateQuery['$gte'] = $startStr;
        }
        if ($end !== null) {
            $endStr            = $end->format(DateTimeInterface::ATOM);
            $dateQuery['$lte'] = $endStr;
        }

        return ['_submission_time' => $dateQuery];
    }

    public static function formatAssetRequestQuery(int $limit = 100, int $offset = 0, string $asset_type = 'survey'): array
    {
        return [
            'limit'  => $limit,
            'offset' => $offset,
            'q'      => 'asset_type:' . $asset_type,
        ];
    }

    public static function formatSubmissionRequestQuery(array $filters = []): array
    {
        $queryArray = [];

        if (isset($filters['start']) || isset($filters['end'])) {
            $filtersQuery = Utils::formatDateFilter($filters['start'] ?? null, $filters['end'] ?? null);

            $queryArray['query'] = json_encode($filtersQuery);
        }

        if (isset($filters['limit'])) {
            $queryArray['limit'] = $filters['limit'];
        }

        if (isset($filters['offset'])) {
            $queryArray['start'] = $filters['offset'];
        }

        return $queryArray;
    }

    public static function formatPermissions(array $permissions): array
    {
        $formattedPermissions = [];

        foreach ($permissions as $permission) {
            $user     = explode('/', $permission['user']);
            $username = array_slice($user, -2, 1)[0];

            $permission = explode('/', $permission['permission']);
            $permission = array_slice($permission, -2, 1)[0];

            $formattedPermissions[$username][] = $permission;
        }

        return $formattedPermissions;
    }
}
