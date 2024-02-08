<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Support\MediaLibrary\Actions\AddMediaAction;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;

class AddImageToGame
{
    public function __construct(private AddMediaAction $addMediaAction, private Filesystem $filesystem)
    {
    }

    public function execute(Game $game, Request|string $source, string $collection): void
    {
        if ($this->addMediaAction->execute($game, $source, $collection)) {
            /*
             * add symlink to Images directory - required to stay backwards compatible with emulators
             */
            $game->load('media');
            $image = $game->getFirstMedia($collection);

            if (realpath($image->getPath()) === false) {
                return;
            }

            $alias = config('filesystems.disks.media.root') . '/Images/' . $image->getCustomProperty('sha1') . '.png';
            $this->filesystem->delete($alias);
            $this->filesystem->link(realpath($image->getPath()), $alias);

            /*
             * TODO: upload to s3 bucket - required to stay backwards compatible with API clients
             * TODO: if the $source is a numeric path, assume that it already existed on s3 -> remove old file
             */
        }
    }
}
