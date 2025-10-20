<?php

namespace KoboSDK;

class Form
{
    public function __construct(
        private string $formId,
        private array $data,
    ) {
        $this->permissions = $this->formatPermissions();
    }

    private function formatPermissions(): array
    {
        $permissions          = $this->data['permissions'] ?? [];
        $formattedPermissions = [];

        foreach ($permissions as $permission) {
            if (isset($permission['type']) && isset($permission['level'])) {
                $formattedPermissions[$permission['type']] = $permission['level'];
            }
        }

        return $formattedPermissions;
    }

    public function permissions(): array
    {
        return $this->permissions;
    }

    //	public function permissions(): array
    //	{
    //		return $this->data['permissions'] ?? [];
    //	}
}
