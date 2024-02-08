<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\IntegrationRelease;
use Illuminate\Filesystem\Filesystem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class LinkLatestIntegrationRelease
{
    public function __construct(private Filesystem $filesystem)
    {
    }

    public function execute(): void
    {
        /** @var ?IntegrationRelease $latestStableMinimumRelease */
        $latestStableMinimumRelease = IntegrationRelease::stable()->latest()->first();

        if (!$latestStableMinimumRelease) {
            return;
        }

        /*
         * Link the latest version to the fallback location for backwards compatibility
         * Note: it does not have to be the minimum version
         * Note: make sure to use realpath for the target, not the release's storage symlink path
         * Otherwise the link may break after subsequent releases
         *
         * Remove existing symlink before adding new avatar
         * Note: do not use Storage::disk()'s ->exist() or ->delete() as both have no idea how to handle symlinks
         */
        $this->filesystem->delete(config('filesystems.disks.media.root') . '/' . $latestStableMinimumRelease->build_x86_permalink);
        $this->filesystem->delete(config('filesystems.disks.media.root') . '/' . $latestStableMinimumRelease->build_x64_permalink);

        $latestStableMinimumRelease->load('media');

        /** @var Media|null $build */
        $build = $latestStableMinimumRelease->getMedia('build_x86')->last();
        if ($build && realpath($build->getPath()) !== false) {
            $this->filesystem->link(
                realpath($build->getPath()),
                config('filesystems.disks.media.root') . '/' . $latestStableMinimumRelease->build_x86_permalink
            );
        }

        /** @var Media|null $build */
        $build = $latestStableMinimumRelease->getMedia('build_x64')->last();
        if ($build && realpath($build->getPath()) !== false) {
            $this->filesystem->link(
                realpath($build->getPath()),
                config('filesystems.disks.media.root') . '/' . $latestStableMinimumRelease->build_x64_permalink
            );
        }
    }
}
