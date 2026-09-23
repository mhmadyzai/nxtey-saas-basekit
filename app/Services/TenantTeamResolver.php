<?php

namespace App\Services;

use Spatie\Permission\Contracts\PermissionsTeamResolver;
use Illuminate\Database\Eloquent\Model;

class TenantTeamResolver implements PermissionsTeamResolver
{
    protected int|string|null $teamId = null;

    public function setPermissionsTeamId(int|string|Model|null $id): void
    {
        if ($id instanceof Model) {
            $id = $id->getKey();
        }
        $this->teamId = $id;
    }

    public function getPermissionsTeamId(): int|string|null
    {
        // Auto-detect from Stancl tenancy context if not explicitly set
        if ($this->teamId === null && function_exists('tenant') && tenant()) {
            return tenant()->getTenantKey();
        }

        return $this->teamId;
    }
}