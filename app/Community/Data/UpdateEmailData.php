<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Community\Requests\UpdateEmailRequest;
use Spatie\LaravelData\Data;

class UpdateEmailData extends Data
{
    public function __construct(
        public string $newEmail,
    ) {
    }

    public static function fromRequest(UpdateEmailRequest $request): self
    {
        return new self(
            newEmail: $request->newEmail,
        );
    }
}
