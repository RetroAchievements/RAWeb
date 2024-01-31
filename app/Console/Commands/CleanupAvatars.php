<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\DeleteAvatar;
use App\Models\User;
use App\Support\MediaLibrary\RejectedHashes;
use Illuminate\Console\Command;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CleanupAvatars extends Command
{
    protected $signature = 'ra:site:user:cleanup-avatars';
    protected $description = 'Delete rejected avatars';

    public function __construct(
        private DeleteAvatar $deleteAvatarAction
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        foreach (RejectedHashes::AVATAR_HASHES as $rejectedAvatarHash) {
            $rejectedMedia = Media::where('model_type', resource_type(User::class))
                ->whereJsonContains('custom_properties->sha1', $rejectedAvatarHash);

            $this->info($rejectedAvatarHash . ' ' . $rejectedMedia->count());

            $rejectedMedia = $rejectedMedia->cursor();

            /** @var Media $media */
            foreach ($rejectedMedia as $media) {
                /** @var User $user */
                $user = $media->model;
                $this->info('  Deleting avatar of ' . $user->username . ': ' . $media->getFullUrl());
                $this->deleteAvatarAction->execute($user);
            }

            $this->info(' ');
        }
    }
}
