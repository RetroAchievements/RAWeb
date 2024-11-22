<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Data\UserData;
use App\Models\Subscription;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('Subscription')]
class SubscriptionData extends Data
{
    public function __construct(
        public int $id,
        #[LiteralTypeScriptType('App.Community.Enums.SubscriptionSubjectType')]
        public string $subjectType,
        public int $subjectId,
        public bool $state,
        public Lazy|UserData $user
    ) {
    }

    public static function fromSubscription(Subscription $subscription): self
    {
        return new self(
            id: $subscription->id,
            subjectType: $subscription->subject_type,
            subjectId: $subscription->subject_id,
            state: $subscription->state,
            user: Lazy::create(fn () => $subscription->user),
        );
    }
}
