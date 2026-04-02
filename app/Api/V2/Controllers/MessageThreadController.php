<?php

declare(strict_types=1);

namespace App\Api\V2\Controllers;

use App\Community\Actions\CreateMessageThreadAction;
use App\Community\Actions\DeleteMessageThreadAction;
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

class MessageThreadController extends JsonApiController
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Destroy;

    /**
     * Override store to use the existing CreateMessageThreadAction.
     */
    public function store(Route $route, StoreContract $store)
    {
        /** @var ResourceRequest $request */
        $request = ResourceRequest::forResource($route->resourceType());

        $query = ResourceQuery::queryOne($route->resourceType());

        $validated = $request->validated();

        /** @var User $sender */
        $sender = $request->user();

        // Resolve recipient from flattened relationship data
        $recipientId = $validated['recipient']['id'] ?? null;
        $recipient = User::where('ulid', $recipientId)
            ->orWhere('display_name', $recipientId)
            ->orWhere('username', $recipientId)
            ->firstOrFail();

        // Create the thread using existing action
        $thread = (new CreateMessageThreadAction())->execute(
            $sender,
            $recipient,
            $sender, // true sender is the same for now
            $validated['title'],
            $validated['body']
        );

        return DataResponse::make($thread)
            ->withQueryParameters($query)
            ->didCreate();
    }

    /**
     * Override destroy to use the existing DeleteMessageThreadAction.
     */
    public function destroy(Route $route, StoreContract $store)
    {
        /** @var ResourceRequest|null $request */
        $request = ResourceRequest::forResourceIfExists($route->resourceType());

        /** @var MessageThread $thread */
        $thread = $route->model();

        /** @var User $user */
        $user = $request?->user() ?? request()->user();

        (new DeleteMessageThreadAction())->execute($thread, $user);

        return response(null, 204);
    }
}
