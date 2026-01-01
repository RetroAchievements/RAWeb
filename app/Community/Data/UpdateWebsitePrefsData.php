<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Community\Requests\UpdateWebsitePrefsRequest;
use Spatie\LaravelData\Data;

class UpdateWebsitePrefsData extends Data
{
    public function __construct(
        public int $preferences_bitfield,
    ) {
    }

    public static function fromRequest(UpdateWebsitePrefsRequest $request): self
    {
        return new self(
            preferences_bitfield: $request->preferencesBitfield,
        );
    }

    public function toArray(): array
    {
        return [
            'preferences_bitfield' => $this->preferences_bitfield,
        ];
    }
}
