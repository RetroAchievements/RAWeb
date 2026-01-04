<?php

declare(strict_types=1);

namespace App\Community\Components;

use App\Community\Enums\Rank;
use App\Community\Enums\RankType;
use App\Enums\Permissions;
use App\Models\User;
use App\Support\Cache\CacheKey;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;

class UserCard extends Component
{
    private string|array $user;

    public function __construct(string|array $user)
    {
        $this->user = $user;
    }

    public function render(): ?View
    {
        $username = $this->getUsername($this->user);

        if (empty($username)) {
            return null;
        }

        /* @var array $rawUserData */
        $rawUserData = [];
        if (is_array($this->user)) {
            $rawUserData = $this->user;
        } else {
            $rawUserData = $this->getUserData($username);
        }

        if (!$rawUserData) {
            return null;
        }

        $cardViewValues = $this->buildAllCardViewValues($username, $rawUserData);

        return view('components.community.user-card', $cardViewValues);
    }

    private function getUsername(string|array $user): ?string
    {
        return is_string($user) ? $user : ($user['User'] ?? null);
    }

    private function getUserData(string $username): ?array
    {
        return Cache::store('array')->rememberForever(
            CacheKey::buildUserCardDataCacheKey($username),
            function () use ($username): ?array {
                $foundUser = User::whereName($username)->first();

                return $foundUser ? [
                    ...$foundUser->toArray(),
                    'isBanned' => $foundUser->isBanned(),
                    'isMuted' => $foundUser->isMuted(),
                    'visibleRoleName' => $foundUser->visible_role?->name,
                ] : null;
            }
        );
    }

    private function buildAllCardViewValues(string $username, array $rawUserData): array
    {
        $cardBioData = $this->buildCardBioData($rawUserData);
        $cardRankData = $this->buildCardRankData($username, $rawUserData['points_hardcore'], $rawUserData['points'] ?? 0, $rawUserData['unranked_at'] !== null);
        $cardRoleData = $this->buildCardRoleData($username, $rawUserData['visibleRoleName'], $rawUserData['isBanned'], $rawUserData['isMuted']);

        return array_merge($cardBioData, $cardRankData, $cardRoleData);
    }

    private function buildCardBioData(array $rawUserData): array
    {
        $username = $rawUserData['display_name'] ?? $rawUserData['username'] ?? "";
        $motto = $rawUserData['motto'] && !$rawUserData['isMuted'] ? $rawUserData['motto'] : null;
        $avatarUrl = $rawUserData['avatarUrl'] ?? null;
        $hardcorePoints = $rawUserData['points_hardcore'] ?? 0;
        $softcorePoints = $rawUserData['points'] ?? 0;
        $retroPoints = $rawUserData['points_weighted'] ?? 0;
        $isUntracked = $rawUserData['unranked_at'] !== null;
        $permissions = $rawUserData['Permissions'] ?? Permissions::Unregistered;
        $memberSince = $rawUserData['created_at'] ?? Carbon::now();
        $lastActivity = $rawUserData['last_activity_at'] ? Carbon::parse($rawUserData['last_activity_at'])->diffForHumans() : null;

        return compact(
            'username',
            'motto',
            'avatarUrl',
            'hardcorePoints',
            'softcorePoints',
            'retroPoints',
            'isUntracked',
            'permissions',
            'memberSince',
            'lastActivity'
        );
    }

    private function buildCardRankData(string $username, int $hardcorePoints, int $softcorePoints, bool $isUntracked): array
    {
        $siteRank = 0;
        $totalRankedUsersCount = 0;
        $rankType = RankType::Hardcore;
        $rankLabel = 'Site Rank';
        $rankPctLabel = '';
        $rankMinPoints = Rank::MIN_POINTS;

        if ($isUntracked) {
            $siteRank = 'Untracked';
            $rankType = 'Untracked';
        } elseif ($hardcorePoints >= $softcorePoints) {
            $rankType = RankType::Hardcore;
            $siteRank = $hardcorePoints < Rank::MIN_POINTS ? 0 : getUserRank($username, $rankType);
        } elseif ($softcorePoints > 0) {
            $rankType = RankType::Softcore;
            $siteRank = $softcorePoints < Rank::MIN_POINTS ? 0 : getUserRank($username, $rankType);
            $rankLabel = 'Softcore Rank';
        }

        if ($rankType !== 'Untracked') {
            $totalRankedUsersCount = countRankedUsers($rankType);

            // Don't divide by zero.
            if ($totalRankedUsersCount === 0) {
                $totalRankedUsersCount = 1;
            }

            $rankPct = sprintf("%1.2f", ($siteRank / $totalRankedUsersCount) * 100.0);
            $rankPctLabel = $siteRank > 100 ? "(Top $rankPct%)" : "";
        }

        return compact(
            'siteRank',
            'totalRankedUsersCount',
            'rankType',
            'rankLabel',
            'rankPctLabel',
            'rankMinPoints',
        );
    }

    private function buildCardRoleData(string $username, ?string $visibleRoleName, bool $isBanned, bool $isMuted): array
    {
        // Priority: Banned > Muted > Role.
        if ($isBanned) {
            $canShowUserRole = true;
            $roleLabel = __('Banned');
        } elseif ($isMuted) {
            $canShowUserRole = true;
            $roleLabel = __('Muted');
        } elseif ($visibleRoleName !== null) {
            $canShowUserRole = true;
            $roleLabel = __('permission.role.' . $visibleRoleName);
        } else {
            $canShowUserRole = false;
            $roleLabel = null;
        }

        $useExtraNamePadding =
            $canShowUserRole
            && ((mb_strlen($roleLabel) >= 14 && mb_strlen($username) >= 14) || mb_strlen($username) >= 16);

        return compact(
            'canShowUserRole',
            'roleLabel',
            'useExtraNamePadding'
        );
    }
}
