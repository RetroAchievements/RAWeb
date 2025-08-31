<?php

declare(strict_types=1);

namespace App\Mail\Data;

class GranularUnsubscribeData extends UnsubscribeData
{
    public function __construct(
        int $userId,
        public string $subjectType,
        public int $subjectId,
    ) {
        parent::__construct($userId, 'granular');
    }
}
