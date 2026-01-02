<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Community\Requests\UpdateProfileRequest;
use Spatie\LaravelData\Data;

class UpdateProfileData extends Data
{
    public function __construct(
        public ?int $visibleRoleId,
        public bool $isUserWallActive,
        public string $motto,
    ) {
    }

    public static function fromRequest(UpdateProfileRequest $request): self
    {
        return new self(
            isUserWallActive: $request->isUserWallActive,
            motto: $request->motto,
            visibleRoleId: $request->visibleRoleId,
        );
    }

    public function toArray(): array
    {
        return [
            'is_user_wall_active' => $this->isUserWallActive,
            'motto' => $this->motto,
            'visible_role_id' => $this->visibleRoleId,
        ];
    }
}
