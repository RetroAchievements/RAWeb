<?php

declare(strict_types=1);

namespace App\Mail\Data;

class CategoryUnsubscribeData extends UnsubscribeData
{
    public function __construct(
        int $userId,
        public int $preference,
    ) {
        parent::__construct($userId, 'category');
    }
}
