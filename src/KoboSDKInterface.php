<?php

namespace KoboSDK;

interface KoboSDKInterface
{
    public function getAssets(): array;

    public function getMedia(): array;

    public function submit(string $formId, array $data): array;

    public function submitXml(string $formId, array $data): array;

    public function asset(string $formId): Asset;

    public function assetContent(string $formId): array;

    public function getSubmissions(string $formId, array $filters = []): array;

    public function submissionRaw(string $formId, string $submissionId): array;

    public function submission(string $formId, string $submissionId, array $keepFields): array;

    public function getEditLink(string $formId, string $submissionId): array;

    public function getSubmissionAttachments(string $formId, string $submissionId, string $path): array;

    public function getAttachment(string $formId, string $submissionId, string $attachmentId): array;
}
