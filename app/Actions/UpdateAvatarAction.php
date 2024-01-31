<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Support\MediaLibrary\Actions\AddMediaAction;
use Illuminate\Http\Request;

class UpdateAvatarAction
{
    public function __construct(
        private AddMediaAction $addMediaAction,
        private LinkLatestAvatarAction $linkLatestAvatarAction
    ) {
    }

    /**
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig
     */
    public function execute(User $user, Request|string $source): void
    {
        /*
         * add avatar, resized variants are created automatically
         */
        if ($this->addMediaAction->execute($user, $source, 'avatar')) {
            /*
             * link the now latest avatar
             */
            $this->linkLatestAvatarAction->execute($user);
        }
    }
}
