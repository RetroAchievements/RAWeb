<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Connect\Actions\GetBadgeIdRangeAction;
use App\Models\Achievement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * @deprecated one-time command
 */
class RenumberOutOfRangeAchievementBadges extends Command
{
    protected $signature = 'ra:platform:renumber-out-of-range-achievement-badges';
    protected $description = 'Fixes a data issue with misnumbered achievement badges across all storage mediums';

    public function handle(): void
    {
        $media = Storage::disk('media');

        $nextBadge = 1 + (int) Achievement::withTrashed()
            ->whereRaw('CAST(image_name AS UNSIGNED) < 1000000')
            ->selectRaw('MAX(CAST(image_name AS UNSIGNED)) AS highest_badge')
            ->value('highest_badge');

        $oldBadges = Achievement::withTrashed()
            ->whereRaw('CAST(image_name AS UNSIGNED) >= 1000000')
            ->pluck('image_name')
            ->unique();

        foreach ($oldBadges as $oldBadge) {
            // Only fix achievement badges after both images are safely copied.
            if (!$media->exists("Badge/{$oldBadge}.png") || !$media->exists("Badge/{$oldBadge}_lock.png")) {
                $this->warn("{$oldBadge}: image files not found on this server, skipped");

                continue;
            }

            while ($media->exists(sprintf('Badge/%05d.png', $nextBadge))) {
                $nextBadge++;
            }
            $newBadge = sprintf('%05d', $nextBadge++);

            foreach (['.png', '_lock.png'] as $suffix) {
                $newPath = "Badge/{$newBadge}{$suffix}";

                if (
                    !$media->copy("Badge/{$oldBadge}{$suffix}", $newPath)
                    || (app()->environment('production') && !UploadToS3($media->path($newPath), $newPath))
                ) {
                    $this->error("{$oldBadge}: could not copy {$newPath}, skipped");

                    continue 2;
                }
            }

            Achievement::withTrashed()->where('image_name', $oldBadge)->get()
                ->each->update(['image_name' => $newBadge]);

            $this->line("{$oldBadge} => {$newBadge}");
        }

        $this->info('Next badge number for uploads: ' . (new GetBadgeIdRangeAction())->execute()['NextBadge']);
    }
}
