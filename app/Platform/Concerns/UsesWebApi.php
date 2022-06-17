<?php

declare(strict_types=1);

namespace App\Platform\Concerns;

use Illuminate\Support\Str;

trait UsesWebApi
{
    public static function bootUsesWebApi(): void
    {
    }

    public function rollApiToken(): void
    {
        do {
            $this->api_token = Str::random(60);
        } while ($this->where('api_token', $this->api_token)->exists());
        $this->save();
    }
}
