<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\TicketSignalVerdict;
use App\Platform\Data\TicketSignalAccuracyData;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\TicketVerdictAccuracyResultFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Captures, per signal verdict, how often that verdict turned out to be correct
 * on the tickets that finished inside a set window.
 *
 * @see MeasureTicketVerdictAccuracy.php
 */
class TicketVerdictAccuracyResult extends BaseModel
{
    /** @use HasFactory<TicketVerdictAccuracyResultFactory> */
    use HasFactory;

    protected $table = 'ticket_verdict_accuracy_results';

    public const UPDATED_AT = null;

    public const MIN_TICKETS_TO_SHOW = 100; // a smaller sample is too noisy to display

    protected $fillable = [
        'verdict',
        'ticket_count',
        'correct_count',
        'window_started_at',
        'window_ended_at',
    ];

    protected $casts = [
        'verdict' => TicketSignalVerdict::class,
        'window_started_at' => 'datetime',
        'window_ended_at' => 'datetime',
    ];

    protected static function newFactory(): TicketVerdictAccuracyResultFactory
    {
        return TicketVerdictAccuracyResultFactory::new();
    }

    // == helpers

    /**
     * @return array<string, TicketSignalAccuracyData> keyed by verdict value, from the newest result per verdict that has enough tickets to show
     */
    public static function findShownAccuracyByVerdict(): array
    {
        return self::query()
            ->where('ticket_count', '>=', self::MIN_TICKETS_TO_SHOW)
            ->orderByDesc('id')
            ->get()
            ->unique(fn (self $accuracyRow) => $accuracyRow->verdict->value)
            ->mapWithKeys(fn (self $accuracyRow) => [
                $accuracyRow->verdict->value => new TicketSignalAccuracyData(
                    ticketCount: $accuracyRow->ticket_count,
                    correctCount: $accuracyRow->correct_count,
                ),
            ])
            ->all();
    }

    // == accessors

    // == mutators

    // == relations

    // == scopes
}
