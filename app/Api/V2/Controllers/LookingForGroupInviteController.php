<?php

declare(strict_types=1);

namespace App\Api\V2\Controllers;

use App\Community\Actions\CancelLookingForGroupInviteAction;
use App\Community\Actions\CreateLookingForGroupInviteAction;
use App\Community\Actions\RespondToLookingForGroupInviteAction;
use App\Models\LookingForGroupInvite;
use App\Models\LookingForGroupPost;
use App\Models\User;
use App\Community\Enums\LookingForGroupInviteStatus;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Contracts\Store\Store as StoreContract;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class LookingForGroupInviteController extends JsonApiController
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;

    /**
     * Override store to use the CreateLookingForGroupInviteAction.
     */
    public function store(Route $route, StoreContract $store)
    {
        /** @var ResourceRequest $request */
        $request = ResourceRequest::forResource($route->resourceType());

        $query = ResourceQuery::queryOne($route->resourceType());

        $validated = $request->validated();

        /** @var User $sender */
        $sender = $request->user();

        // Resolve LFG post from relationship data
        $postId = $validated['lookingForGroupPost']['id'];
        /** @var LookingForGroupPost $post */
        $post = LookingForGroupPost::findOrFail($postId);

        // Resolve recipient from relationship data
        $recipientId = $validated['recipient']['id'];
        $recipient = User::where('ulid', $recipientId)
            ->orWhere('display_name', $recipientId)
            ->orWhere('username', $recipientId)
            ->firstOrFail();

        // Create the invite using existing action
        $invite = (new CreateLookingForGroupInviteAction())->execute(
            $post,
            $sender,
            $recipient,
            $validated['message'] ?? null
        );

        return DataResponse::make($invite)
            ->withQueryParameters($query)
            ->didCreate();
    }

    /**
     * Override update to handle status transitions.
     */
    public function update(Route $route, StoreContract $store)
    {
        /** @var ResourceRequest $request */
        $request = ResourceRequest::forResource($route->resourceType());

        $query = ResourceQuery::queryOne($route->resourceType());

        /** @var LookingForGroupInvite $invite */
        $invite = $route->model();
        $validated = $request->validated();

        /** @var User $user */
        $user = $request->user();

        // Handle status transitions
        if (isset($validated['status'])) {
            $newStatus = LookingForGroupInviteStatus::from($validated['status']);

            if ($newStatus === LookingForGroupInviteStatus::Accepted || $newStatus === LookingForGroupInviteStatus::Declined) {
                $invite = (new RespondToLookingForGroupInviteAction())->execute($invite, $user, $newStatus);
            } elseif ($newStatus === LookingForGroupInviteStatus::Canceled) {
                $invite = (new CancelLookingForGroupInviteAction())->execute($invite, $user);
            } else {
                throw new \InvalidArgumentException('Invalid status transition.');
            }
        }

        return DataResponse::make($invite)->withQueryParameters($query);
    }
}
