<?php

namespace KoboSDK;

interface KoboSDKInterface
{
    public function getAssets(): array;

    public function getSubmissions(string $formId, array $filters = []): array;
}
