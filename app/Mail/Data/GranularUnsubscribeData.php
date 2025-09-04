<?php

declare(strict_types=1);

namespace App\Mail\Data;

use App\Community\Enums\SubscriptionSubjectType;

class GranularUnsubscribeData extends UnsubscribeData
{
    public function __construct(
        int $userId,
        public SubscriptionSubjectType $subjectType,
        public int $subjectId,
        public ?bool $previousState = null,
    ) {
        parent::__construct($userId, 'granular');
    }
}
