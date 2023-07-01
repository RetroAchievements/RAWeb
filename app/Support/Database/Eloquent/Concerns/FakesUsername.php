<?php

declare(strict_types=1);

namespace App\Support\Database\Eloquent\Concerns;

trait FakesUsername
{
    protected function fakeUsername(): string
    {
        return mb_substr(str_replace('.', '', fake()->unique()->userName), 0, 20);
    }
}
