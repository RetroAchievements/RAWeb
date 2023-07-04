@props([
    "user"
])

<?php
use App\Community\Enums\Rank;
use App\Community\Enums\RankType;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
use Illuminate\Support\Carbon;

// The $user prop must be a string or array. We don't have a good
// way of enforcing this in anonymous Blade components via static
// analysis alone, so catch this case and throw an error if it occurs.
if (!is_string($user) && !is_array($user)) {
    throw new InvalidArgumentException("Invalid type for user. Expected string or array.");
}

$userName = is_string($user) ? $user : ($user['User'] ?? null);
if (empty($userName)) {
    return null;
}

/* @var User|array $userData */
$userData = [];
if (is_array($user)) {
    $userData = $user;
}

if (empty($userData)) {
    $userData = Cache::store('array')->rememberForever('user:' . $userName . ':card-data', function () use ($userName) {
        return User::firstWhere('User', $userName);
    });
}

// If we can't find the user, then we can't render a tooltip. Bail.
if (!$userData) {
    return null;
}

$userMotto = $userData->Motto ?? null;
$userAvatarUrl = $userData->AvatarUrl ?? null;
$userHardcorePoints = $userData->RAPoints ?? 0;
$userSoftcorePoints = $userData->RASoftcorePoints ?? 0;
$userRetroPoints = $userData->TrueRAPoints ?? 0;
$userUntracked = $userData->Untracked ?? false;
$userPermissions = $userData->Permissions ?? Permissions::Unregistered;
$userMemberSince = $userData->Created ?? Carbon::now();

$userLastActivity = $userData->LastLogin ? Carbon::parse($userData->LastLogin)->diffForHumans() : null;

$userRank = 0;
$totalRankedUsersCount = 0;
$rankType = null;
$userRankLabel = 'Site Rank:';
$userRankPctLabel = '';

if ($userUntracked) {
    $userRank = 'Untracked';
    $rankType = 'Untracked';
} elseif ($userHardcorePoints >= $userSoftcorePoints) {
    $rankType = RankType::Hardcore;
    $userRank = $userHardcorePoints < Rank::MIN_POINTS ? 0 : getUserRank($userName, $rankType);
} elseif ($userSoftcorePoints > 0) {
    $rankType = RankType::Softcore;
    $userRank = $userSoftcorePoints < Rank::MIN_POINTS ? 0 : getUserRank($userName, $rankType);
    $userRankLabel = 'Softcore Rank:';
}

if ($rankType !== 'Untracked') {
    $totalRankedUsersCount = countRankedUsers($rankType);
    $rankPct = sprintf("%1.2f", ($userRank / $totalRankedUsersCount) * 100.0);
    $userRankPctLabel = $userRank > 100 ? "(Top $rankPct%)" : "";
}

$canShowUserRole = $userPermissions !== Permissions::Registered;
$userRoleLabel = Permissions::toString($userPermissions);
$useExtraNamePadding = $canShowUserRole && ((mb_strlen($userRoleLabel) >= 14 && mb_strlen($userName) >= 14) || mb_strlen($userName) >= 16)
?>

@if($userPermissions >= Permissions::Unregistered)
    <x-tooltip-card imgSrc="{{ $userAvatarUrl }}">
        <div class="relative h-full text-2xs">
            <!-- Role -->
            @if($canShowUserRole)
                <div class="absolute top-[-14px] right-[-21px]">
                    <p class="text-2xs tracking-tighter flex items-center justify-center pl-2 pr-5 pt-2 bg-menu-link text-box-bg rounded">
                        {{ $userRoleLabel }}
                    </p>
                </div>
            @endif        
            
            <!-- Username -->
            <p class="font-bold text-lg -mt-1 {{ $useExtraNamePadding ? "pt-3" : "" }}">{{ $userName }}</p>

            <!-- Motto -->
            @if($userMotto !== null && mb_strlen($userMotto) > 2)
                <div class="mb-1 rounded bg-bg text-text italic p-1 text-2xs hyphens-auto">
                    <p>{{ $userMotto }}</p>
                </div>
            @endif

            <!-- Points -->
            @if($userHardcorePoints > $userSoftcorePoints)
                <p>
                    <span class="font-bold">Points:</span>
                    {{ localized_number($userHardcorePoints) }}
                    ({{ localized_number($userRetroPoints) }})
                </p>
            @elseif($userSoftcorePoints > 0)
                <p>
                    <span class="font-bold">Softcore Points:</span>
                    {{ localized_number($userSoftcorePoints) }}
                </p>
            @else
                <p><span class="font-bold">Points:</span> 0</p>
            @endif

            <!-- Site Rank -->
            <p>
                <span class="font-bold">{{ $userRankLabel }}</span>
                @if($userUntracked)
                    <span>Untracked</span>
                @else
                    {{ $userRank === 0 ? "Needs at least " . Rank::MIN_POINTS . " points" : "#" . localized_number($userRank) }}
                    {{ $userRankPctLabel }}
                @endif
            </p>

            <!-- Last Activity -->
            @if($userLastActivity)
                <p>
                    <span class="font-bold">Last Activity:</span>
                    {{ $userLastActivity }}
                </p>
            @endif

            <!-- Member Since -->
            @if($userMemberSince)
                <p>
                    <span class="font-bold">Member Since:</span>
                    {{ getNiceDate(strtotime($userMemberSince), $justDay = true) }}
                </p>
            @endif
        </div>
    </x-tooltip-card>
@endif