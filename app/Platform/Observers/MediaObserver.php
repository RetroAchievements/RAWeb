<?php

declare(strict_types=1);

namespace App\Platform\Observers;

use App\Models\Game;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
        $newUrl = $this->getMediaUrl($media);

        // Get the previous banner (if any) to show in the audit log as "old".
        // The previous current banner was marked as is_current: false before save.
        $previousBanner = $game->getMedia('banner')
            ->where('id', '!=', $media->id)
            ->where('custom_properties.is_current', false)
            ->sortByDesc('created_at')
            ->first();

        $oldUrl = $previousBanner ? $this->getMediaUrl($previousBanner) : null;

        activity()
            ->causedBy(Auth::user())
            ->performedOn($game)
            ->withProperty('attributes', ['banner' => $newUrl])
            ->withProperty('old', ['banner' => $oldUrl])
            ->event('updated')
            ->log('updated');
    }

    public function deleting(Media $media): bool
    {
        // Only handle banner deletions for Game models.
        if (!$media->model instanceof Game) {
            return true;
        }

        if ($media->collection_name !== 'banner') {
            return true;
        }

        // Prevent automatic deletion of banners by the Spatie component.
        // Instead, mark the banner as not current so it's preserved but hidden from the form.
        // This allows us to keep banner history.
        $media->setCustomProperty('is_current', false);
        $media->save();

        // Return false to cancel the actual deletion.
        return false;
    }

    /**
     * Construct the media URL using the Storage facade.
     */
    private function getMediaUrl(Media $media): string
    {
        return Storage::disk($media->disk)->url($media->getPath());
    }
}
