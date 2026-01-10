<?php

declare(strict_types=1);

namespace App\Platform\Observers;

use App\Models\Game;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaObserver
{
    public function created(Media $media): void
    {
        // Only log banner changes for Game models.
        if (!$media->model instanceof Game) {
            return;
        }

        if ($media->collection_name !== 'banner') {
            return;
        }

        $game = $media->model;

        activity()
            ->causedBy(Auth::user())
            ->performedOn($game)
            ->withProperty('attributes', ['banner' => $media->file_name])
            ->withProperty('old', ['banner' => null])
            ->event('updated')
            ->log('updated');
    }

    public function deleting(Media $media): void
    {
        // Only log banner deletions for Game models.
        if (!$media->model instanceof Game) {
            return;
        }

        if ($media->collection_name !== 'banner') {
            return;
        }

        $game = $media->model;

        activity()
            ->causedBy(Auth::user())
            ->performedOn($game)
            ->withProperty('attributes', ['banner' => null])
            ->withProperty('old', ['banner' => $media->file_name])
            ->event('updated')
            ->log('updated');
    }
}
