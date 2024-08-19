<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Community\Requests\UpdatePasswordRequest;
use Spatie\LaravelData\Data;

class UpdatePasswordData extends Data
{
    public function __construct(
        public string $newPassword,
    ) {
    }

    public static function fromRequest(UpdatePasswordRequest $request): self
    {
        return new self(
            newPassword: $request->newPassword,
        );
    }
}
