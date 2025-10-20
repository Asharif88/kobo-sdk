<?php

namespace KoboSDK;

use DateTimeImmutable;
use DateTimeInterface;

final class Utils
{
    public static function formatDateFilter(?DateTimeImmutable $start = null, ?DateTimeImmutable $end = null): array
    {
        // {"_submission_time":{"$gte":"2023-06-01T00:00:00+00:00","$lte":"2023-06-30T23:59:59+00:00"}}
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

    public static function formatRequestQuery(array $filters = []): array
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
}
