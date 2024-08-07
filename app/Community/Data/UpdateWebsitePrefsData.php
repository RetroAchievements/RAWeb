<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Community\Requests\UpdateWebsitePrefsRequest;
use Spatie\LaravelData\Data;

class UpdateWebsitePrefsData extends Data
{
    public function __construct(
        public int $websitePrefs,
    ) {
    }

    public static function fromRequest(UpdateWebsitePrefsRequest $request): self
    {
        return new self(
            websitePrefs: $request->websitePrefs,
        );
    }

    public function toArray(): array
    {
        return [
            'websitePrefs' => $this->websitePrefs,
        ];
    }
}
