<?php

declare(strict_types=1);

namespace App\Support\Database\Eloquent\Concerns;

trait HasFullTableName
{
    public static function getFullTableName(): string
    {
        $model = new static();

        return $model->getConnection()->getTablePrefix() . $model->getTable();
    }
}
