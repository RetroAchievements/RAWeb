<?php

declare(strict_types=1);

namespace App\Api\V2\Controllers;

use Illuminate\Http\Response;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;

class EventAchievementController extends JsonApiController
{
    use Actions\FetchOne;

    /**
     * Use GET /events/{id}/event-achievements instead.
     */
    public function index(Route $route, Store $store): Response
    {
        abort(404);
    }
}
