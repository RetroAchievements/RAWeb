<?php

declare(strict_types=1);

namespace App\Enums;

enum GameHashCompatibility: string
{
    // players are allowed to earn achievements with this hash
    case Compatible = 'compatible';

    // players are not allowed to earn achievements with this hash
    case Incompatible = 'incompatible';

    // players are not allowed to earn achievements with this hash, but they could be compatible
    case Untested = 'untested';

    // players are not allowed to earn achievements with this hash until they apply a patch
    case PatchRequired = 'patch-required';

    public function label(): string
    {
        return static::getLabel($this);
    }

    public static function getLabel(self $value): string
    {
        return match ($value) {
            GameHashCompatibility::Compatible => 'Compatible',
            GameHashCompatibility::Incompatible => 'Incompatible',
            GameHashCompatibility::Untested => 'Untested',
            GameHashCompatibility::PatchRequired => 'Patch required',
        };
    }
}
