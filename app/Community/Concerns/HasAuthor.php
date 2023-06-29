<?php

declare(strict_types=1);

namespace App\Community\Concerns;

trait HasAuthor
{
    public static function bootHasAuthor(): void
    {
        static::creating(function ($model) {
            /*
             * Make sure an user id is set for the author when creating this model
             */
            if (request()->user()) {
                $model->setAttribute('user_id', request()->user()->ID);
            }
        });
    }
}
