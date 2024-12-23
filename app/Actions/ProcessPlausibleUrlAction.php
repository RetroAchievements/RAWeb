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

        // Routes with nested ID-slug segments.
        // TODO $this->addNestedRoute('forums', ['category', 'forum']);
    }

    public function execute(string $url): array
    {
        $url = trim($url, '/');
        $segments = explode('/', $url);
        if (count($segments) < 2) {
            return ['redactedUrl' => "/{$url}", 'props' => []];
        }

        $path = $segments[0];
        $param = $segments[1];
        $suffix = count($segments) > 2 ? '/' . implode('/', array_slice($segments, 2)) : '';

        if (!isset($this->routes[$path])) {
            // Handle unknown paths that might have numeric IDs.
            if (is_numeric($param)) {
                return [
                    'redactedUrl' => "/{$path}/_PARAM_{$suffix}",
                    'props' => ['id' => (int) $param],
                ];
            }

            return ['redactedUrl' => "/{$url}", 'props' => []];
        }

        $route = $this->routes[$path];
        $props = [];

        switch ($route['type']) {
            case 'model':
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
                $props = [$route['propName'] => $param];
                break;

            case 'id':
                if (is_numeric($param)) {
                    $props = ['id' => (int) $param];
                }
                break;

            case 'nested':
                // TODO return $this->handleNestedRoute($route, $segments);
        }

        return [
            'redactedUrl' => "/{$path}/_PARAM_{$suffix}",
            'props' => $props,
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
    private function extractId(string $param): ?int
    {
        // Check for slug format first (eg: "sonic-3-123").
        if (preg_match('/-(\d+)$/', $param, $matches)) {
            return (int) $matches[1];
        }

        // Fall back to direct ID if it's numeric.
        return is_numeric($param) ? (int) $param : null;
    }
}
