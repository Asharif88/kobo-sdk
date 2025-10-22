<?php

namespace KoboSDK;

class Asset
{
    public array $permissions;

    public string $type;

    public array $content;

    public string $uid;

    public string $name;

    public function __construct(
        public string $formId,
        public array $data,
    ) {
        $this->permissions = Utils::formatPermissions($this->data['permissions'] ?? []);
        $this->content     = $this->data['content'];
        $this->type        = $this->data['asset_type'];
        $this->uid         = $this->data['uid'];
        $this->name        = $this->data['name'];
    }

    public function permissions(): array
    {
        return $this->permissions;
    }

    public function submissions(): array
    {
        return $this->content;
    }

    public function content(): array
    {
        return $this->content;
    }
}
