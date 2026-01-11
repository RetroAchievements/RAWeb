<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class UsersOnlineCount extends Model
{
    protected $table = 'users_online_counts';

    public const UPDATED_AT = null;

    protected $fillable = [
        'online_count',
        'created_at',
    ];

    // == accessors

    // == mutators

    // == relations

    // == scopes

    // == helpers

    /**
     * Log the current online count, backfilling any missed intervals with zeroes.
     */
    public static function log(int $count): self
    {
        self::backfillMissedIntervals();

        return self::create(['online_count' => $count]);
    }

    /**
     * If we missed any intervals due to an outage, backfill those rows with zeroes.
     */
    private static function backfillMissedIntervals(): void
    {
        $lastRecord = self::latest('created_at')->first();
        if (!$lastRecord) {
            return;
        }

        $minutesSinceLastRecord = (int) $lastRecord->created_at->diffInMinutes(Carbon::now());
        $missedIntervals = intdiv($minutesSinceLastRecord, 30) - 1;

        for ($i = 1; $i <= $missedIntervals; $i++) {
            self::create([
                'online_count' => 0,
                'created_at' => $lastRecord->created_at->copy()->addMinutes(30 * $i),
            ]);
        }
    }
}
