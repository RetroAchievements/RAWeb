<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Data\UserData;
use App\Http\Controller;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\User;
use App\Platform\Data\AchievementData;
use App\Platform\Data\GameData;
use App\Platform\Data\GameSetData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchApiController extends Controller
{
    private const DEFAULT_SCOPES = ['users'];
    private const VALID_SCOPES = ['users', 'games', 'hubs', 'achievements'];
    private const MIN_QUERY_LENGTH = 3;
    private const MAX_RESULTS_PER_SCOPE = 10;

    public function index(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->query('q', ''));

        // Searches must be at least 3 characters long.
        if (mb_strlen($keyword) < self::MIN_QUERY_LENGTH) {
            return response()->json([
                'results' => [],
                'query' => $keyword,
                'scopes' => [],
            ]);
        }

        // Parse scopes from the optional 'scope' query parameter.
        // Scopes can be used to narrow searches down to certain kinds of entities.
        $requestedScopes = $this->parseScopes($request->query('scope'));

        // Use array_fill_keys for more efficient result initialization.
        $results = array_fill_keys($requestedScopes, []);

        /**
         * TODO can we use the Concurrency facade for this?
         * @see https://laravel.com/docs/11.x/concurrency
         */
        $scopeMap = [
            'users' => fn () => $this->searchUsers($keyword),
            'games' => fn () => $this->searchGames($keyword),
            'hubs' => fn () => $this->searchHubs($keyword),
            'achievements' => fn () => $this->searchAchievements($keyword),
        ];

        // Scout doesn't have the ability to directly leverage a multi-indexed search.
        // We haphazardly weigh the various scope results ourselves. It's probably good enough.
        $scopeRelevance = [];
        foreach ($requestedScopes as $scope) {
            if (isset($scopeMap[$scope])) {
                $scopeResults = $scopeMap[$scope]();
                $results[$scope] = $scopeResults['results'];
                $scopeRelevance[$scope] = $scopeResults['avgRelevance'];
            }
        }

        return response()->json([
            'results' => $results,
            'query' => $keyword,
            'scopes' => $requestedScopes,
            'scopeRelevance' => $scopeRelevance,
        ]);
    }

    private function parseScopes(?string $scopeParam): array
    {
        if (empty($scopeParam)) {
            return self::DEFAULT_SCOPES;
        }

        $requestedScopes = explode(',', $scopeParam);
        $validScopes = array_intersect($requestedScopes, self::VALID_SCOPES);

        return !empty($validScopes) ? $validScopes : self::DEFAULT_SCOPES;
    }

    /**
     * Returns a float from 0.0 (not relevant) to 1.0 (very relevant).
     *
     * Calculate a score from 0.0 to 1.0 based on Levenshtein distance and vibes.
     * This is different from Meilisearch's relevance ranking and is complementary to it.
     * Meilisearch does a good job of ranking individual scope results by relevance, but
     * we also need to weigh the scopes themselves by the relevance of their internal entries
     * against the user's original query. This helps us know what scopes the user might care about most.
     *
     * This is only used to determine what order the scopes should be presented as in the UI.
     */
    private function calculateTitleRelevance(string $query, string $title): float
    {
        $query = mb_strtolower($query);
        $title = mb_strtolower($title);

        // Exact match gets a perfect score.
        if ($query === $title) {
            return 1.0;
        }

        // Use Levenshtein distance normalized by the longer string length.
        $distance = levenshtein($query, $title);
        $maxLength = max(mb_strlen($query), mb_strlen($title));

        // Convert distance to similarity (0 distance = 1.0 similarity).
        $similarity = 1.0 - ($distance / $maxLength);

        // Boost if title contains the exact query.
        if (str_contains($title, $query)) {
            $similarity = max($similarity, 0.7);
        }

        return max(0.0, $similarity);
    }

    /**
     * Detect if a given query is likely searching for a hub based on common patterns.
     * Be very conservative - only boost hubs when we're confident.
     */
    private function detectHubIntent(string $query): bool
    {
        $lowerQuery = mb_strtolower($query);

        $hubPrefixes = [
            'series', 'developer', 'genre', 'subgenre',
            'asb', 'hacks', 'credits', 'theme', 'publisher',
            'subseries', 'homebrew', 'misc.', 'devquest', 'central',
        ];

        foreach ($hubPrefixes as $prefix) {
            // Check both with and without leading bracket.
            if (str_starts_with($lowerQuery, $prefix) || str_starts_with($lowerQuery, '[' . $prefix)) {
                return true;
            }
        }

        // This is basically always a hub search.
        if (str_contains($query, 'meta|')) {
            return true;
        }

        // If the user starts or ends their query with a square bracket, it's a
        // very strong signal they're intentionally searching for a hub.
        if (str_starts_with($query, '[') || str_ends_with($query, ']')) {
            return true;
        }

        return false;
    }

    private function searchUsers(string $keyword): array
    {
        $searchResults = User::search($keyword)
            ->orderBy('last_activity_at', 'desc')
            ->take(20)
            ->get();

        $filteredUsers = $searchResults
            ->filter(fn ($user) => $user->email_verified_at !== null)
            ->take(self::MAX_RESULTS_PER_SCOPE);

        $results = $filteredUsers->map(function ($user) {
            return UserData::fromUser($user)->include('lastActivityAt', 'points', 'pointsSoftcore');
        });

        // Calculate average relevance for section ordering.
        $avgRelevance = $filteredUsers->isEmpty() ? 0 : $filteredUsers->map(function ($user) use ($keyword) {
            return $this->calculateTitleRelevance($keyword, $user->display_name);
        })->avg();

        return [
            'results' => $results->values(),
            'avgRelevance' => $avgRelevance,
        ];
    }

    private function searchGames(string $keyword): array
    {
        $games = Game::search($keyword)
            ->take(self::MAX_RESULTS_PER_SCOPE)
            ->get();

        $games->load('system');

        $results = $games->map(function ($game) {
            return GameData::fromGame($game)->include(
                'badgeUrl',
                'system.iconUrl',
                'system.nameShort',
                'achievementsPublished',
                'pointsTotal',
                'playersTotal'
            );
        });

        // Calculate average relevance just for section ordering.
        $avgRelevance = $games->isEmpty() ? 0 : $games->map(function ($game) use ($keyword) {
            return $this->calculateTitleRelevance($keyword, $game->title);
        })->avg();

        return [
            'results' => $results->values(),
            'avgRelevance' => $avgRelevance,
        ];
    }

    private function searchHubs(string $keyword): array
    {
        $hubs = GameSet::search($keyword)
            ->take(self::MAX_RESULTS_PER_SCOPE)
            ->get();

        $hubs->loadCount(['games', 'children as link_count']);

        // Check if the user is likely to be searching for a hub.
        $hasHubIntent = $this->detectHubIntent($keyword);

        $results = $hubs->map(function ($hub) {
            return GameSetData::fromGameSetWithCounts($hub)->include('badgeUrl', 'gameCount', 'linkCount');
        });

        // Calculate average relevance for UI section ordering.
        $avgRelevance = $hubs->isEmpty() ? 0 : $hubs->map(function ($hub) use ($keyword, $hasHubIntent) {
            $relevanceScore = $this->calculateTitleRelevance($keyword, $hub->title);

            // Apply hub intent boost if necessary.
            if ($hasHubIntent && $relevanceScore > 0) {
                $relevanceScore = min(1.0, $relevanceScore * 1.5);
            }

            return $relevanceScore;
        })->avg();

        return [
            'results' => $results->values(),
            'avgRelevance' => $avgRelevance,
        ];
    }

    private function searchAchievements(string $keyword): array
    {
        $achievements = Achievement::search($keyword)
            ->take(self::MAX_RESULTS_PER_SCOPE)
            ->get();

        $achievements->load('game.system');

        $results = $achievements->map(function ($achievement) {
            $achievementData = AchievementData::fromAchievement($achievement)->include(
                'badgeUnlockedUrl',
                'description',
                'game',
                'points',
                'pointsWeighted',
            );

            $gameData = GameData::fromGame($achievement->game)->include(
                'id',
                'system.iconUrl',
                'system.nameShort',
                'title',
            );

            $achievementData->game = $gameData;

            return $achievementData;
        });

        // Calculate average relevance for section ordering.
        $avgRelevance = $achievements->isEmpty() ? 0 : $achievements->map(function ($achievement) use ($keyword) {
            return $this->calculateTitleRelevance($keyword, $achievement->title);
        })->avg();

        return [
            'results' => $results->values(),
            'avgRelevance' => $avgRelevance,
        ];
    }
}
