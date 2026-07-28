<?php

declare(strict_types=1);

namespace App\Enums;

enum SetClaimChangeAction: string
{
    case Create = 'create';
    case Extend = 'extend';
    case Drop = 'drop';
    case Update = 'update';

    public function label(): string
    {
        return match ($this) {
            self::Create => 'created',
            self::Extend => 'extended',
            self::Drop => 'dropped',
            self::Update => 'completed',
        };
    }
}
