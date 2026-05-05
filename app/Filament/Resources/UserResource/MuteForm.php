<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource;

use App\Models\User;
use Carbon\Carbon;
use DateTimeInterface;

final class MuteForm
{
    public const ACTION_NONE = 'none';
    public const ACTION_KEEP = 'keep';
    public const ACTION_THREE_DAYS = '3_days';
    public const ACTION_ONE_WEEK = '1_week';
    public const ACTION_TWO_WEEKS = '2_weeks';
    public const ACTION_ONE_MONTH = '1_month';
    public const ACTION_THREE_MONTHS = '3_months';
    public const ACTION_PERMANENT = 'permanent';
    public const ACTION_CUSTOM = 'custom';
    public const ACTION_UNMUTE = 'unmute';

    public const PERMANENT_MUTE_DATE = '2038-01-18'; // db upper limit

    private const DURATION_LABELS = [
        self::ACTION_THREE_DAYS => '3 days',
        self::ACTION_ONE_WEEK => '1 week',
        self::ACTION_TWO_WEEKS => '2 weeks',
        self::ACTION_ONE_MONTH => '1 month',
        self::ACTION_THREE_MONTHS => '3 months',
    ];

    /**
     * @return array<string, string>
     */
    public static function optionsFor(?User $user): array
    {
        if (self::isPermanentlyMuted($user)) {
            return [
                self::ACTION_KEEP => 'Keep permanent mute',
                self::ACTION_CUSTOM => 'Change end date',
                self::ACTION_UNMUTE => 'Unmute',
            ];
        }

        if (self::isActivelyMuted($user)) {
            return array_merge([
                self::ACTION_KEEP => 'Keep current mute',
            ], self::durationOptions('Extend by'), [
                self::ACTION_PERMANENT => 'Make permanent',
                self::ACTION_CUSTOM => 'Change end date',
                self::ACTION_UNMUTE => 'Unmute',
            ]);
        }

        return array_merge([
            self::ACTION_NONE => 'Not muted',
        ], self::durationOptions('Mute for'), [
            self::ACTION_PERMANENT => 'Mute permanently',
            self::ACTION_CUSTOM => 'Custom date',
        ]);
    }

    public static function defaultActionFor(?User $user): string
    {
        return self::isActivelyMuted($user) ? self::ACTION_KEEP : self::ACTION_NONE;
    }

    public static function defaultCustomDateFor(?User $user): string
    {
        if (self::isActivelyMuted($user)) {
            return $user->muted_until->copy()->utc()->toDateString();
        }

        return Carbon::now('UTC')->addWeek()->toDateString();
    }

    public static function currentStatusFor(?User $user): string
    {
        if (!self::isActivelyMuted($user)) {
            return 'Not muted.';
        }

        if (self::isPermanentlyMuted($user)) {
            return 'Muted permanently.';
        }

        return 'Muted until ' . self::formatMutedUntil($user->muted_until) . '.';
    }

    public static function previewFor(?User $user, ?string $action, mixed $customMutedUntil): string
    {
        $action ??= self::defaultActionFor($user);

        if ($action === self::ACTION_NONE || $action === self::ACTION_UNMUTE) {
            return 'No active mute.';
        }

        if ($action === self::ACTION_KEEP) {
            return self::currentStatusFor($user);
        }

        if ($action === self::ACTION_PERMANENT) {
            return self::isActivelyMuted($user)
                ? 'Changes mute to permanent.'
                : 'Muted permanently.';
        }

        if ($action === self::ACTION_CUSTOM && !$customMutedUntil) {
            return 'Choose a custom end date.';
        }

        $mutedUntil = self::resolveMutedUntil($user, $action, $customMutedUntil);

        if (!$mutedUntil) {
            return 'No active mute.';
        }

        $prefix = self::isActivelyMuted($user) && $action !== self::ACTION_CUSTOM
            ? 'Extends mute to '
            : 'Muted until ';

        return $prefix . self::formatMutedUntil($mutedUntil) . '.';
    }

    public static function resolveMutedUntil(?User $user, ?string $action, mixed $customMutedUntil = null): ?Carbon
    {
        $action ??= self::defaultActionFor($user);

        return match ($action) {
            self::ACTION_NONE, self::ACTION_UNMUTE => null,

            self::ACTION_KEEP => self::isActivelyMuted($user) ? $user?->muted_until : null,
            self::ACTION_PERMANENT => self::permanentMutedUntil(),
            self::ACTION_CUSTOM => self::parseDatePickerDate($customMutedUntil),

            self::ACTION_THREE_DAYS,
            self::ACTION_ONE_WEEK,
            self::ACTION_TWO_WEEKS,
            self::ACTION_ONE_MONTH,
            self::ACTION_THREE_MONTHS => self::resolveDurationMutedUntil($user, $action),

            default => self::isActivelyMuted($user) ? $user?->muted_until : null,
        };
    }

    public static function isActivelyMuted(?User $user): bool
    {
        return $user?->muted_until?->isFuture() === true;
    }

    private static function isPermanentlyMuted(?User $user): bool
    {
        if (!self::isActivelyMuted($user)) {
            return false;
        }

        return $user->muted_until->copy()->utc()->startOfDay()->gte(self::permanentMutedUntil());
    }

    /**
     * @return array<string, string>
     */
    private static function durationOptions(string $prefix): array
    {
        $options = [];

        foreach (self::DURATION_LABELS as $action => $label) {
            $options[$action] = "{$prefix} {$label}";
        }

        return $options;
    }

    private static function resolveDurationMutedUntil(?User $user, string $action): Carbon
    {
        $base = Carbon::now('UTC')->startOfDay();

        if ($user?->muted_until?->isFuture() === true && !self::isPermanentlyMuted($user)) {
            $base = $user->muted_until->copy()->utc()->startOfDay();
        }

        return match ($action) {
            self::ACTION_THREE_DAYS => $base->addDays(3),
            self::ACTION_ONE_WEEK => $base->addWeek(),
            self::ACTION_TWO_WEEKS => $base->addWeeks(2),
            self::ACTION_ONE_MONTH => $base->addMonthNoOverflow(),
            self::ACTION_THREE_MONTHS => $base->addMonthsNoOverflow(3),
            default => $base,
        };
    }

    private static function parseDatePickerDate(mixed $date): ?Carbon
    {
        if ($date instanceof DateTimeInterface) {
            return Carbon::parse($date)->utc()->startOfDay();
        }

        if (!is_string($date) || $date === '') {
            return null;
        }

        return Carbon::parse($date, 'UTC')->startOfDay();
    }

    private static function permanentMutedUntil(): Carbon
    {
        return Carbon::parse(self::PERMANENT_MUTE_DATE, 'UTC')->startOfDay();
    }

    private static function formatMutedUntil(DateTimeInterface $mutedUntil): string
    {
        return Carbon::parse($mutedUntil)->utc()->format('M j, Y') . ' at midnight UTC';
    }
}
