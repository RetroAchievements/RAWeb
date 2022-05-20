<?php

declare(strict_types=1);

namespace App\Site\Actions;

use App\Site\Models\User;
use Illuminate\Filesystem\Filesystem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class LinkLatestAvatarAction
{
    public function __construct(
        private Filesystem $filesystem
    ) {
    }

    /**
     * Link the latest media in avatar collection of a user to the static path as well as s3 for full backwards compatibility.
     * Local storage and public paths should be linked with MediaLink command.
     */
    public function execute(User $user): void
    {
        /**
         * TODO: change username to be display_name and unique as lowercase
         * otherwise the display_name cannot ever be changed
         */
        // $linkPath = '/UserPic/' . $user->username . '.png';
        $linkPath = '/UserPic/' . $user->display_name . '.png';

        /*
         * Remove existing symlink before adding new avatar
         * Note: do not use Storage::disk()'s ->exist() or ->delete() as both have no idea how to handle symlinks
         */
        $this->filesystem->delete(config('filesystems.disks.media.root') . $linkPath);

        $user->load('media');

        $avatarPath = null;

        /*
         * Link default avatar for local development - on production this is done by nginx serving the fallback image
         */
        // if (app()->environment('local')) {
        //     $avatarPath = public_path('assets/images/user/avatar.webp');
        // }

        if ($user->hasMedia('avatar')) {
            /** @var Media $media */
            $media = $user->getMedia('avatar')->last();
            $avatarPath = $media->getPath('2xl');
        }

        /*
         * Create symlink
         * Note: avoid linking to a release's storage path
         */
        if ($avatarPath && realpath($avatarPath) !== false) {
            $this->filesystem->link(realpath($avatarPath), config('filesystems.disks.media.root') . $linkPath);
        }

        /*
         * TODO: Upload & replace on s3 for backup
         */
    }
}
