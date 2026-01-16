<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Community\Data\CommentData;
use App\Community\Enums\CommentableType;
use App\Data\ForumTopicCommentData;
use App\Data\UserData;
use App\Http\Controller;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Event;
use App\Models\ForumTopicComment;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\User;
use App\Platform\Data\AchievementData;
use App\Platform\Data\EventData;
use App\Platform\Data\GameData;
use App\Platform\Data\GameSetData;
use App\Support\Shortcode\Shortcode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SearchApiController extends Controller
{
    private const DEFAULT_SCOPES = ['users'];
    private const VALID_SCOPES = ['users', 'games', 'hubs', 'events', 'achievements', 'forum_comments', 'comments'];
    private const MIN_QUERY_LENGTH = 3;
    private const MAX_RESULTS_PER_SCOPE = 10;
    private const DEFAULT_PER_PAGE = 25;

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

        // Parse any given pagination parameters.
        // These are optional and only used for single-scope searches.
        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('perPage', self::DEFAULT_PER_PAGE)));

        // Use a paginated search when the client explicitly requests pagination for a single scope.
        $shouldUsePagination = $request->has('page') && count($requestedScopes) === 1;

        // Use array_fill_keys for more efficient result initialization.
        $results = array_fill_keys($requestedScopes, []);

        /**
         * TODO can we use the Concurrency facade for this?
         * @see https://laravel.com/docs/11.x/concurrency
         */
        $scopeMap = [
            'users' => fn () => $this->searchUsers($keyword, $shouldUsePagination ? $page : null, $shouldUsePagination ? $perPage : null),
            'games' => fn () => $this->searchGames($keyword, $shouldUsePagination ? $page : null, $shouldUsePagination ? $perPage : null),
            'hubs' => fn () => $this->searchHubs($keyword, $shouldUsePagination ? $page : null, $shouldUsePagination ? $perPage : null),
            'events' => fn () => $this->searchEvents($keyword, $shouldUsePagination ? $page : null, $shouldUsePagination ? $perPage : null),
            'achievements' => fn () => $this->searchAchievements($keyword, $shouldUsePagination ? $page : null, $shouldUsePagination ? $perPage : null),
            'forum_comments' => fn () => $this->searchForumComments($keyword, $shouldUsePagination ? $page : null, $shouldUsePagination ? $perPage : null),
            'comments' => fn () => $this->searchComments($keyword, $request->user(), $shouldUsePagination ? $page : null, $shouldUsePagination ? $perPage : null),
        ];

        // Scout doesn't have the ability to directly leverage a multi-indexed search.
        // We haphazardly weigh the various scope results ourselves. It's probably good enough.
        $scopeRelevance = [];
        $pagination = null;
        foreach ($requestedScopes as $scope) {
            if (isset($scopeMap[$scope])) {
                $scopeResults = $scopeMap[$scope]();
                $results[$scope] = $scopeResults['results'];
                $scopeRelevance[$scope] = $scopeResults['avgRelevance'];

                // Capture pagination metadata when using paginated search.
                if ($shouldUsePagination && isset($scopeResults['pagination'])) {
                    $pagination = $scopeResults['pagination'];
                }
            }
        }

        $response = [
            'results' => $results,
            'query' => $keyword,
            'scopes' => $requestedScopes,
            'scopeRelevance' => $scopeRelevance,
        ];

        if ($pagination) {
            $response['pagination'] = $pagination;
        }

        return response()->json($response);
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
     * @param LengthAwarePaginator<int, mixed> $paginator
     */
    private function buildPaginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'perPage' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /**
     * @param Collection<int, mixed> $results
     */
    private function buildSearchResponse(
        Collection $results,
        float $avgRelevance,
        ?array $pagination = null,
    ): array {
        $response = [
            'results' => $results->values(),
            'avgRelevance' => $avgRelevance,
        ];

        if ($pagination !== null) {
            $response['pagination'] = $pagination;
        }

        return $response;
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
     * Preprocess game search keywords to handle special cases.
     * This ensures queries like ".hack" match the intended games rather than generic "hack" games.
     */
    private function preprocessGameSearchKeyword(string $keyword): string
    {
        // Replace ".hack" with its "dothack" alias to match our indexed variations.
        // This prevents ".hack" from being simplified to just "hack" by Meilisearch
        // when Meilisearch automatically fuzzes out special characters (".").
        if (stripos($keyword, '.hack') !== false) {
            return str_ireplace('.hack', 'dothack', $keyword);
        }

        return $keyword;
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

    private function searchUsers(string $keyword, ?int $page = null, ?int $perPage = null): array
    {
        if ($page !== null) {
            // Use paginated search. Fetch more than needed to filter unverified users.
            $paginator = User::search($keyword)
                ->where('is_banned', false)
                ->orderBy('last_activity_at', 'desc')
                ->paginate($perPage, 'page', $page);

            /** @var EloquentCollection<int, User> $users */
            $users = new EloquentCollection($paginator->items());
            $filteredUsers = $users->filter(fn ($user) => $user->email_verified_at !== null);

            $results = $filteredUsers->map(function ($user) {
                return UserData::fromUser($user)->include('lastActivityAt', 'points', 'pointsSoftcore');
            });

            return $this->buildSearchResponse($results, 0.5, $this->buildPaginationMeta($paginator));
        }

        $searchResults = User::search($keyword)
            ->where('is_banned', false)
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
            $relevance = $this->calculateTitleRelevance($keyword, $user->display_name);

            // If the query contains multiple words, it's less likely to be a username.
            // Usernames are always single words (eg: "PokemonRedVersion").
            $wordCount = str_word_count($keyword);
            if ($wordCount > 1 && $relevance < 1.0) {
                // Reduce relevance for multi-word queries that aren't exact matches.
                $relevance *= 0.7;
            }

            return $relevance;
        })->avg();

        return $this->buildSearchResponse($results, $avgRelevance);
    }

    private function searchGames(string $keyword, ?int $page = null, ?int $perPage = null): array
    {
        $processedKeyword = $this->preprocessGameSearchKeyword($keyword);

        if ($page !== null) {
            $paginator = Game::search($processedKeyword)->paginate($perPage, 'page', $page);

            /** @var EloquentCollection<int, Game> $games */
            $games = new EloquentCollection($paginator->items());
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

            return $this->buildSearchResponse($results, 0.5, $this->buildPaginationMeta($paginator));
        }

        $games = Game::search($processedKeyword)
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
            $relevance = $this->calculateTitleRelevance($keyword, $game->title);

            // Apply a small boost for multi-word queries since game titles often have multiple words.
            $wordCount = str_word_count($keyword);
            if ($wordCount > 1 && $relevance > 0.5) {
                // Boost relevance for multi-word queries with good matches.
                $relevance = min(1.0, $relevance * 1.2);
            }

            return $relevance;
        })->avg();

        return $this->buildSearchResponse($results, $avgRelevance);
    }

    private function searchHubs(string $keyword, ?int $page = null, ?int $perPage = null): array
    {
        if ($page !== null) {
            $paginator = GameSet::search($keyword)->paginate($perPage, 'page', $page);

            /** @var EloquentCollection<int, GameSet> $hubs */
            $hubs = new EloquentCollection($paginator->items());
            $hubs->loadCount(['games', 'children as link_count']);

            $results = $hubs->map(function ($hub) {
                return GameSetData::fromGameSetWithCounts($hub)->include('badgeUrl', 'gameCount', 'linkCount');
            });

            return $this->buildSearchResponse($results, 0.5, $this->buildPaginationMeta($paginator));
        }

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

            // Apply a small boost for multi-word queries since hub titles often have multiple words.
            $wordCount = str_word_count($keyword);
            if ($wordCount > 1 && $relevanceScore > 0.5) {
                // Boost relevance for multi-word queries with good matches.
                $relevanceScore = min(1.0, $relevanceScore * 1.2);
            }

            return $relevanceScore;
        })->avg();

        return $this->buildSearchResponse($results, $avgRelevance);
    }

    private function searchEvents(string $keyword, ?int $page = null, ?int $perPage = null): array
    {
        if ($page !== null) {
            $paginator = Event::search($keyword)->paginate($perPage, 'page', $page);

            /** @var EloquentCollection<int, Event> $events */
            $events = new EloquentCollection($paginator->items());
            $events->load('achievements', 'legacyGame');

            $results = $events->map(function ($event) {
                return EventData::fromEvent($event)->include(
                    'legacyGame.badgeUrl',
                    'state',
                );
            });

            return $this->buildSearchResponse($results, 0.5, $this->buildPaginationMeta($paginator));
        }

        $events = Event::search($keyword)
            ->take(self::MAX_RESULTS_PER_SCOPE)
            ->get();

        $events->load('achievements', 'legacyGame');

        $results = $events->map(function ($event) {
            return EventData::fromEvent($event)->include(
                'legacyGame.badgeUrl',
                'state',
            );
        });

        // Calculate average relevance for UI section ordering.
        $avgRelevance = $events->isEmpty() ? 0 : $events->map(function ($event) use ($keyword) {
            return $this->calculateTitleRelevance($keyword, $event->legacyGame->title);
        })->avg();

        return $this->buildSearchResponse($results, $avgRelevance);
    }

    private function searchAchievements(string $keyword, ?int $page = null, ?int $perPage = null): array
    {
        if ($page !== null) {
            $paginator = Achievement::search($keyword)->paginate($perPage, 'page', $page);

            /** @var EloquentCollection<int, Achievement> $achievements */
            $achievements = new EloquentCollection($paginator->items());
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

            return $this->buildSearchResponse($results, 0.5, $this->buildPaginationMeta($paginator));
        }

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

        return $this->buildSearchResponse($results, $avgRelevance);
    }

    private function searchForumComments(string $keyword, ?int $page = null, ?int $perPage = null): array
    {
        if ($page !== null) {
            $paginator = ForumTopicComment::search($keyword)->paginate($perPage, 'page', $page);

            /** @var EloquentCollection<int, ForumTopicComment> $comments */
            $comments = new EloquentCollection($paginator->items());
            $comments->load(['user', 'forumTopic.user']);

            $results = $comments->map(function ($comment) {
                $data = ForumTopicCommentData::fromForumTopicComment($comment)->include('forumTopic');
                $data->body = Shortcode::stripAndClamp(html_entity_decode($data->body), 200);

                return $data;
            });

            return $this->buildSearchResponse($results, 0.5, $this->buildPaginationMeta($paginator));
        }

        $comments = ForumTopicComment::search($keyword)
            ->take(self::MAX_RESULTS_PER_SCOPE)
            ->get();

        $comments->load(['user', 'forumTopic.user']);

        $results = $comments->map(function ($comment) {
            $data = ForumTopicCommentData::fromForumTopicComment($comment)->include('forumTopic');
            $data->body = Shortcode::stripAndClamp(html_entity_decode($data->body), 200);

            return $data;
        });

        // Calculate average relevance for section ordering.
        // For comments, we use a base relevance since the search is on body content.
        $avgRelevance = $comments->isEmpty() ? 0 : 0.5;

        return $this->buildSearchResponse($results, $avgRelevance);
    }

    private function searchComments(string $keyword, ?User $user, ?int $page = null, ?int $perPage = null): array
    {
        // Build the base search query.
        $searchQuery = Comment::search($keyword);

        // Guests should not see ticket comments.
        if (!$user) {
            $searchQuery->whereIn('commentable_type', [
                CommentableType::Game->value,
                CommentableType::Achievement->value,
                CommentableType::User->value,
                CommentableType::Leaderboard->value,
            ]);
        }

        if ($page !== null) {
            $paginator = $searchQuery->paginate($perPage, 'page', $page);

            /** @var EloquentCollection<int, Comment> $comments */
            $comments = new EloquentCollection($paginator->items());
            $comments->load('user');

            $results = $comments->map(function ($comment) {
                return CommentData::fromComment($comment);
            });

            return $this->buildSearchResponse($results, 0.5, $this->buildPaginationMeta($paginator));
        }

        $comments = $searchQuery
            ->take(self::MAX_RESULTS_PER_SCOPE)
            ->get();

        $comments->load('user');

        $results = $comments->map(function ($comment) {
            return CommentData::fromComment($comment);
        });

        // Calculate average relevance for section ordering.
        // For comments, we use a base relevance since the search is on body content.
        $avgRelevance = $comments->isEmpty() ? 0 : 0.5;

        return $this->buildSearchResponse($results, $avgRelevance);
    }
}
