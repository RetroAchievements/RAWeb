<?php

declare(strict_types=1);

namespace App\Support\Filesystem;

use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Filesystem\Filesystem;

class LinkStorage extends Command
{
    protected $signature = 'ra:storage:link';

    protected $description = 'Create symbolic storage links';

    /**
     * Execute the console command.
     *
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        /** @var Filesystem $filesystem */
        $filesystem = $this->laravel->make('files');

        /*
         * add public storage paths for local development - will be served through media host on production server
         * where these carry over between deployments as the storage directory will be completely replaced by a symlink
         */
        if (app()->environment('local')) {
            $filesystem->delete(public_path('media'));
            $filesystem->link(config('filesystems.disks.media.root'), public_path('media'));
            $filesystem->delete(public_path('Badge'));
            $filesystem->link(config('filesystems.disks.media.root') . '/Badge', public_path('Badge'));
            $filesystem->delete(public_path('Images'));
            $filesystem->link(config('filesystems.disks.media.root') . '/Images', public_path('Images'));
            $filesystem->delete(public_path('bin'));
            $filesystem->link(config('filesystems.disks.media.root') . '/bin', public_path('bin'));
            $filesystem->delete(public_path('UserPic'));
            $filesystem->link(config('filesystems.disks.media.root') . '/UserPic', public_path('UserPic'));
            $filesystem->delete(public_path('docs'));
            $filesystem->link(config('filesystems.disks.static.root') . '/docs', public_path('docs'));

            // legacy
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

        // legacy
        $filesystem->delete(public_path('LatestIntegration.html'));
        $filesystem->link(storage_path('app/LatestIntegration.html'), public_path('LatestIntegration.html'));

        /*
         * replace linked default user avatar
         * Note: should be safe images that can be displayed well in emulator
         */
        $filesystem->delete(config('filesystems.disks.media.root') . '/UserPic/_User.png');
        $filesystem->link(config('filesystems.disks.static.root') . '/assets/images/user/avatar-safe.png', config('filesystems.disks.media.root') . '/UserPic/_User.png');

        $filesystem->delete(config('filesystems.disks.media.root') . '/Images/000001.png');
        $filesystem->link(config('filesystems.disks.static.root') . '/assets/images/game/icon-safe.png', config('filesystems.disks.media.root') . '/Images/000001.png');

        /*
         * re-link public assets and vendor folders in reverse - will be served through static host on production server
         * these do not carry over between deployments
         */
        $filesystem->delete(config('filesystems.disks.static.root') . '/assets');
        $filesystem->link(public_path('assets'), config('filesystems.disks.static.root') . '/assets');
        $filesystem->delete(config('filesystems.disks.static.root') . '/vendor');
        $filesystem->link(public_path('vendor'), config('filesystems.disks.static.root') . '/vendor');

        $filesystem->delete(config('filesystems.disks.static.root') . '/docs');
        $filesystem->link(base_path('docs/dist'), config('filesystems.disks.static.root') . '/docs');

        $this->info('Media and static storage have been linked.');
    }
}
