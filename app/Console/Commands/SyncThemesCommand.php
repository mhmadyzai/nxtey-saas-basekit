<?php

namespace App\Console\Commands;

use App\Models\Theme;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SyncThemesCommand extends Command
{
    protected $signature = 'themes:sync';
    protected $description = 'Sync themes from disk to the central registry';

    public function handle(): int
    {
        $themesPath = config('themer.themes_path');
        $directories = File::directories($themesPath);

        foreach ($directories as $dir) {
            $manifest = $dir . '/theme.json';
            if (! File::exists($manifest)) {
                continue;
            }

            $data = json_decode(File::get($manifest), true);
            $name = basename($dir);

            Theme::updateOrCreate(
                ['name' => $name],
                [
                    'display_name' => $data['name'] ?? $name,
                    'version' => $data['version'] ?? '1.0.0',
                    'parent' => $data['parent'] ?? null,
                    'removable' => $data['removable'] ?? true,
                    'disableable' => $data['disableable'] ?? true,
                    'metadata' => $data,
                ]
            );

            $this->info("Synced: {$name}");
        }

        return self::SUCCESS;
    }
}