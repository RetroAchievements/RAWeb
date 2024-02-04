<?php

declare(strict_types=1);

namespace App\Concerns;

trait HasPreferences
{
    public static function bootHasPreferences(): void
    {
    }

    // == accessors

    public function getLocaleAttribute(): string
    {
        return !empty($this->attributes['locale']) ? $this->attributes['locale'] : config('app.locale');
    }

    public function getLocaleDateAttribute(): string
    {
        return !empty($this->attributes['locale_date']) ? $this->attributes['locale_date'] : $this->locale;
    }

    public function getLocaleNumberAttribute(): string
    {
        return !empty($this->attributes['locale_number']) ? $this->attributes['locale_number'] : $this->locale;
    }

    public function getTimezoneAttribute(): string
    {
        return !empty($this->attributes['timezone']) ? $this->attributes['timezone'] : config('app.timezone');
    }
}
