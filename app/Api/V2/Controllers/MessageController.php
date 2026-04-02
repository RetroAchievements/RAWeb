<?php

declare(strict_types=1);

namespace App\Api\V2\Controllers;

use App\Community\Actions\AddToMessageThreadAction;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Contracts\Store\Store as StoreContract;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class MessageController extends JsonApiController
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;

    /**
     * Override store to use the existing AddToMessageThreadAction for replies.
     */
    public function store(Route $route, StoreContract $store)
    {
        /** @var ResourceRequest $request */
        $request = ResourceRequest::forResource($route->resourceType());

        $query = ResourceQuery::queryOne($route->resourceType());

        $validated = $request->validated();

        /** @var User $author */
        $author = $request->user();

        // Get thread ID from messageThread relationship
        $threadId = $validated['messageThread']['id'];
        /** @var MessageThread $thread */
        $thread = MessageThread::findOrFail($threadId);

        // Add the message using existing action
        (new AddToMessageThreadAction())->execute(
            $thread,
            $author,
            $author, // true sender is the same for now
            $validated['body']
        );

        // Return the newly created message
        $message = $thread->messages()->latest()->first();

        return DataResponse::make($message)
            ->withQueryParameters($query)
            ->didCreate();
    }

    /**
     * Override index to prevent direct listing of all messages.
     */
    public function index(Route $route, StoreContract $store)
    {
        abort(404, 'Messages can only be accessed via their thread relationship.');
    }
}
