<?php

namespace App\Tenancy\Bootstrappers;

use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper as BaseFilesystemTenancyBootstrapper;

class CustomFilesystemTenancyBootstrapper extends BaseFilesystemTenancyBootstrapper
{
    public function bootstrap(Tenant $tenant)
    {
        parent::bootstrap($tenant);

        // Ensure the public disk URL is tenant-aware for asset previews
        $this->app['config']["filesystems.disks.public.url"] = 
            tenant_asset('');
    }
}