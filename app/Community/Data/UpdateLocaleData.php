<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Community\Requests\UpdateLocaleRequest;
use Spatie\LaravelData\Data;

class UpdateLocaleData extends Data
{
    public function __construct(
        public string $locale,
    ) {
    }

    public static function fromRequest(UpdateLocaleRequest $request): self
    {
        return new self(
            locale: $request->locale,
        );
    }
}
