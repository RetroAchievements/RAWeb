<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Community\Requests\StoreUsernameChangeRequest;
use Spatie\LaravelData\Data;

class StoreUsernameChangeData extends Data
{
    public function __construct(
        public string $newDisplayName
    ) {
    }

    public static function fromRequest(StoreUsernameChangeRequest $request): self
    {
        return new self(
            newDisplayName: $request->newDisplayName,
        );
    }
}
