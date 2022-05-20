<?php

declare(strict_types=1);

namespace App\Support\Filesystem;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Console\StorageLinkCommand;

class LinkStorage extends StorageLinkCommand
{
    protected $signature = 'ra:storage:link
                {--relative : Create the symbolic link using relative paths}
                {--force : Recreate existing symbolic links}';

    protected static $defaultName = 'ra:storage:link';

    public function handle(): void
    {
        if (app()->environment('local')) {
            $this->localLinks();
        }

        parent::handle();
    }

    private function localLinks(): void
    {
        /** @var Filesystem $filesystem */
        $filesystem = $this->laravel->make('files');

        /*
         * add public storage paths for local development - will be served through media host on production server
         * where these carry over between deployments as the storage directory will be completely replaced by a symlink
         */
        $this->laravel['config']['filesystems.links'] = array_merge($this->laravel['config']['filesystems.links'], [
            public_path('media') => config('filesystems.disks.media.root'),
            public_path('docs') => config('filesystems.disks.static.root') . '/docs',
        ]);

        // (re)move legacy files
        $filesystem->delete(public_path('NewsIter.txt'));
        if ($filesystem->exists(public_path('BadgeIter.txt'))) {
            $filesystem->move(public_path('BadgeIter.txt'), storage_path('app/BadgeIter.txt'));
        }
        if ($filesystem->exists(public_path('ImageIter.txt'))) {
            $filesystem->move(public_path('ImageIter.txt'), storage_path('app/ImageIter.txt'));
        }
        if ($filesystem->exists(base_path('lib/database/releases.php'))) {
            $filesystem->move(base_path('lib/database/releases.php'), storage_path('app/releases.php'));
        }
        // TODO: replace LatestIntegration.html with integration release management and V2 connect API
        if ($filesystem->exists(public_path('LatestIntegration.html')) && !$filesystem->exists(storage_path('app/LatestIntegration.html'))) {
            $filesystem->move(public_path('LatestIntegration.html'), storage_path('app/LatestIntegration.html'));
        }
    }
}
