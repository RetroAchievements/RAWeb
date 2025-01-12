<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\System;

/**
 * For Plausible page views, we want to aggregate like-pages into single groups.
 * For example, /game/1 and /game/5 should not be considered as separate pages for
 * the purpose of page view tracking.
 *
 * Routes are defined using simple configuration objects that specify their type:
 * - 'id': Routes that just use a numeric ID (eg: /ticket/123)
 * - 'string': Routes that use a string parameter (eg: /user/username)
 * - 'model': Routes that can be accessed by ID or slug (eg: /game/sonic-3-123)
 * - 'nested': Routes with multiple ID-slug segments (eg: /forums/1-community/16-chit-chat/create-topic)
 * - 'legacy': Legacy routes that need special handling (eg: viewtopic.php?t=123)
 */
class ProcessPlausibleUrlAction
{
    private array $routes = [];

    /**
     * If you're adding new routes to be tracked, you only need to worry about
     * updating this function.
     */
    public function __construct()
    {
        // Routes that support self-healing URLs (or will in the future) with ID lookups.
        $this->addModelRoute('game', Game::class, 'title');
        $this->addModelRoute('achievement', Achievement::class, 'title');
        $this->addModelRoute('hub', GameSet::class, 'title');
        $this->addModelRoute('system', System::class, 'name');

        // Routes that just use a string parameter.
        $this->addStringRoute('user', 'username');

        // Routes that just use an ID.
        $this->addIdRoute('ticket');

        // Legacy routes that need special handling.
        $this->addLegacyRoute('leaderboardinfo.php', ['i' => 'id']);
        $this->addLegacyRoute('viewtopic.php', ['t' => 'topicId']);
        $this->addLegacyRoute('viewforum.php', ['f' => 'forumId']);
        $this->addLegacyRoute('forum.php', ['c' => 'categoryId']);

        // Routes with nested ID-slug segments.
        // TODO $this->addNestedRoute('forums', ['category', 'forum']);
    }

    public function execute(string $url, array $queryParams = [], array $defaultProps = []): array
    {
        // Split the URL into path components.
        $path = trim($url, '/');
        $segments = explode('/', $path);
        if (count($segments) < 1) {
            return [
                'redactedUrl' => "/{$path}",
                'props' => $defaultProps,
            ];
        }

        $routePath = $segments[0];
        $param = $segments[1] ?? null;
        $suffix = count($segments) > 2 ? '/' . implode('/', array_slice($segments, 2)) : '';

        if (!isset($this->routes[$routePath])) {
            // Handle unknown paths that might have numeric IDs.
            if ($param && is_numeric($param)) {
                return [
                    'redactedUrl' => "/{$routePath}/_PARAM_{$suffix}",
                    'props' => ['id' => (int) $param] + $defaultProps,
                ];
            }

            return [
                'redactedUrl' => "/{$path}",
                'props' => $defaultProps,
            ];
        }

        $route = $this->routes[$routePath];
        $props = [];

        switch ($route['type']) {
            case 'model':
                if ($param === null) {
                    return [
                        'redactedUrl' => "/{$routePath}",
                        'props' => $defaultProps,
                    ];
                }

                $id = $this->extractId($param);
                if ($id && $model = $route['model']::find($id)) {
                    $props = [
                        'id' => $id,
                        strtolower($route['titleField']) => $model->{$route['titleField']},
                    ];
                } elseif ($id) {
                    $props = ['id' => $id];
                }
                break;

            case 'string':
                if ($param === null) {
                    return [
                        'redactedUrl' => "/{$routePath}",
                        'props' => $defaultProps,
                    ];
                }

                $props = [$route['propName'] => $param];
                break;

            case 'id':
                if ($param === null) {
                    return [
                        'redactedUrl' => "/{$routePath}",
                        'props' => $defaultProps,
                    ];
                }

                if ($param && is_numeric($param)) {
                    $props = ['id' => (int) $param];
                }
                break;

            case 'legacy':
                foreach ($route['queryMap'] as $queryParam => $propName) {
                    if (isset($queryParams[$queryParam])) {
                        $props[$propName] = (int) $queryParams[$queryParam];
                    }
                }

                return [
                    'redactedUrl' => "/{$routePath}",
                    'props' => $props + $defaultProps,
                ];

            case 'nested':
                // TODO return $this->handleNestedRoute($route, $segments);
        }

        return [
            'redactedUrl' => "/{$routePath}/_PARAM_{$suffix}",
            'props' => $props + $defaultProps,
        ];
    }

    /**
     * Adds a route that supports both direct ID and slug-with-ID access.
     */
    private function addModelRoute(string $path, string $model, string $titleField): void
    {
        $this->routes[$path] = [
            'type' => 'model',
            'model' => $model,
            'titleField' => $titleField,
        ];
    }

    /**
     * Adds a route that uses a string parameter.
     */
    private function addStringRoute(string $path, string $propName): void
    {
        $this->routes[$path] = [
            'type' => 'string',
            'propName' => $propName,
        ];
    }

    /**
     * Adds a route that just uses an ID parameter.
     */
    private function addIdRoute(string $path): void
    {
        $this->routes[$path] = [
            'type' => 'id',
        ];
    }

    /**
     * Adds a legacy route that needs special query parameter handling.
     */
    private function addLegacyRoute(string $path, array $queryMap): void
    {
        $this->routes[$path] = [
            'type' => 'legacy',
            'queryMap' => $queryMap,
        ];
    }

    /**
     * Adds a route with nested ID-slug segments.
     */
    // private function addNestedRoute(string $path, array $segments): void
    // {
    //     $this->routes[$path] = [
    //         'type' => 'nested',
    //         'segments' => $segments,
    //     ];
    // }

    /** Processes a nested route with ID-slug segments. */
    // private function handleNestedRoute(array $route, array $urlSegments): array
    // {
    //     // TODO
    //     return [];
    // }

    /**
     * Extracts an ID from either a direct ID or a slug-with-ID route.
     */
    private function extractId(?string $param): ?int
    {
        if (!$param) {
            return null;
        }

        // Check for slug format first (eg: "sonic-3-123").
        if (preg_match('/-(\d+)$/', $param, $matches)) {
            return (int) $matches[1];
        }

        // Fall back to direct ID if it's numeric.
        return is_numeric($param) ? (int) $param : null;
    }
}
