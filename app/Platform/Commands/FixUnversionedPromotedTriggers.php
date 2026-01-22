<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Trigger;
use Illuminate\Console\Command;

class FixUnversionedPromotedTriggers extends Command
{
    protected $signature = 'ra:platform:fix-unversioned-promoted-triggers';
    protected $description = 'Fixes promoted achievements that have unversioned triggers';

    public function handle(): void
    {
        // Set version = 1 for achievements that only have NULL version triggers.
        $updated = Trigger::whereNull('version')
            ->where('triggerable_type', 'achievement')
            ->whereIn('triggerable_id', function ($query) {
                $query->select('id')
                    ->from('achievements')
                    ->where('is_promoted', true);
            })
            ->whereNotIn('triggerable_id', function ($query) {
                $query->select('triggerable_id')
                    ->from('triggers')
                    ->where('triggerable_type', 'achievement')
                    ->whereNotNull('version');
            })
            ->update(['version' => 1]);

        $this->info("Set version = 1 on {$updated} triggers.");

        // Delete null version triggers where versioned triggers already exist.
        $deleted = Trigger::whereNull('version')
            ->where('triggerable_type', 'achievement')
            ->whereIn('triggerable_id', function ($query) {
                $query->select('id')
                    ->from('achievements')
                    ->where('is_promoted', true);
            })
            ->delete();

        $this->info("Deleted {$deleted} obsolete NULL version triggers.");
    }
}
