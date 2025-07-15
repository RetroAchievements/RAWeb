<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\UserData;
use App\Models\User;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('UserCredits')]
class UserCreditsData extends Data
{
    public function __construct(
        public string $displayName,
        public string $avatarUrl,
        public int $count,
        public ?Carbon $dateCredited = null,
        public Lazy|Carbon|null $deletedAt = null,
    ) {
    }

    public static function fromUserWithCount(
        User $user,
        int $count,
        ?Carbon $dateCredited = null
    ): self {
        $userData = UserData::fromUser($user)->include('displayName', 'avatarUrl');

        return new self(
            displayName: $userData->displayName,
            avatarUrl: $userData->avatarUrl,
            count: $count,
            dateCredited: $dateCredited,
            deletedAt: Lazy::create(fn () => $user->Deleted),
        );
    }
}
