<?php

declare(strict_types=1);

namespace App\Platform\Jobs;

use App\Platform\Actions\RegenerateGameScreenshotConversionsAction;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class RegenerateGameScreenshotConversionsBatchJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param array<int> $mediaIds
     */
    public function __construct(
        private readonly array $mediaIds,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'regenerate-game-screenshot-conversions:' . count($this->mediaIds),
        ];
    }

    public function handle(): void
    {
        $action = new RegenerateGameScreenshotConversionsAction();

        $mediaItems = Media::with('model')
            ->whereIn('id', $this->mediaIds)
            ->get();

        foreach ($mediaItems as $media) {
            try {
                $action->execute($media);
            } catch (Throwable $e) {
                Log::error("RegenerateGameScreenshotConversionsBatchJob: failed for media {$media->id}", [
                    'exception' => $e,
                ]);
            }
        }
    }
}
