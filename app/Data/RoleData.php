<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\Permission\Models\Role;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('Role')]
class RoleData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }

    public static function fromRole(Role $role): self
    {
        return new self(
            id: $role->id,
            name: $role->name,
        );
    }
}
