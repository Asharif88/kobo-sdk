<?php

namespace KoboSDK;

interface KoboSDKInterface
{
    public function getAssets(): array;

    public function asset(string $formId): Asset;

    public function assetContent(string $formId): array;

    public function getSubmissions(string $formId, array $filters = []): array;

    public function submission(string $formId, string $submissionId): array;

    public function getEditLink(string $formId, string $submissionId): array;
}
