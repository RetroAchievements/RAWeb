<?php

declare(strict_types=1);

namespace App\Api\V2\Controllers;

use Illuminate\Http\Response;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;

class AchievementSetController extends JsonApiController
{
    use Actions\FetchOne;

    /**
     * Index is not supported for achievement sets.
     * Use GET /games/{id}?include=achievementSets instead.
     */
    public function index(Route $route, Store $store): Response
    {
        abort(404);
    }
}
