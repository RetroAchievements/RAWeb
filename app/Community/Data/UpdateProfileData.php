<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Community\Requests\UpdateProfileRequest;
use Spatie\LaravelData\Data;

class UpdateProfileData extends Data
{
    public function __construct(
        public string $motto,
        public bool $userWallActive,
    ) {
    }

    public static function fromRequest(UpdateProfileRequest $request): self
    {
        return new self(
            motto: $request->motto,
            userWallActive: $request->userWallActive,
        );
    }

    public function toArray(): array
    {
        return [
            'Motto' => $this->motto,
            'UserWallActive' => $this->userWallActive,
        ];
    }
}
