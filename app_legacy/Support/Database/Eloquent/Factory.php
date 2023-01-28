<?php

declare(strict_types=1);

namespace LegacyApp\Support\Database\Eloquent;

use Illuminate\Database\Eloquent\Factories\Factory as BaseFactory;

/**
 * @template TModel of BaseModel
 */
abstract class Factory extends BaseFactory
{
    protected function fakeUsername(): string
    {
        return mb_substr(str_replace('.', '', $this->faker->unique()->userName), 0, 20);
    }
}
