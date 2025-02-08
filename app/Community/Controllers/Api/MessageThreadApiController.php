<?php

declare(strict_types=1);

namespace App\Community\Controllers\Api;

use App\Community\Actions\DeleteMessageThreadAction;
use App\Http\Controller;
use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageThreadApiController extends Controller
{
    public function destroy(Request $request, MessageThread $messageThread): JsonResponse
    {
        $this->authorize('delete', $messageThread);

        /** @var User $user */
        $user = $request->user();

        (new DeleteMessageThreadAction())->execute($messageThread, $user);

        return response()->json(['success' => true]);
    }
}
