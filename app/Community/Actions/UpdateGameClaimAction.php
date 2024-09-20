<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Community\Requests\UpdateGameClaimRequest;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Support\Cache\CacheKey;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UpdateGameClaimAction
{
    public function execute(AchievementSetClaim $claim, UpdateGameClaimRequest $request): void
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $newValues = $request->validated();

        if (array_key_exists('status', $newValues)) {
            $newStatus = $newValues['status'];
            if ($claim->Status != $newStatus) {
                $claim->Status = $newStatus;
                if (!ClaimStatus::isActive($newStatus)) {
                    $claim->Finished = Carbon::now();
                }
            }
        }

        $claim->save();
    }
}
