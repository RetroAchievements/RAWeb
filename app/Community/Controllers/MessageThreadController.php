<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\BuildMessageThreadIndexPagePropsAction;
use App\Community\Actions\BuildMessageThreadShowPagePropsAction;
use App\Community\Actions\DeleteMessageThreadAction;
use App\Http\Controller;
use App\Models\MessageThread;
use App\Models\MessageThreadParticipant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MessageThreadController extends Controller
{
    public function index(Request $request): InertiaResponse|RedirectResponse
    {
        $this->authorize('viewAny', MessageThread::class);

        /** @var User $user */
        $user = $request->user();
        $currentPage = (int) request()->input('page', 1);

        $actionResult = (new BuildMessageThreadIndexPagePropsAction())->execute(
            $user,
            $currentPage
        );

        if ($actionResult['redirectToPage'] !== null) {
            return redirect()->route('message-thread.index', [
                'page' => $actionResult['redirectToPage'],
            ]);
        }

        return Inertia::render('messages', $actionResult['props']);
    }

    public function show(Request $request, MessageThread $messageThread): InertiaResponse|RedirectResponse
    {
        $this->authorize('view', $messageThread);

        /** @var User $user */
        $user = $request->user();
        $currentPage = (int) $request->input('page', 1);

        $actionResult = (new BuildMessageThreadShowPagePropsAction())->execute(
            $messageThread,
            $user,
            $currentPage
        );

        if ($actionResult['redirectToPage'] !== null) {
            return redirect()->route('message-thread.show', [
                'page' => $actionResult['redirectToPage'],
            ]);
        }

        return Inertia::render('messages/[messageThread]', $actionResult['props']);
    }

    public function destroy(Request $request, MessageThread $messageThread): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $participating = MessageThreadParticipant::where('thread_id', $messageThread->id)
            ->where('user_id', $user->ID)
            ->exists();

        if (!$participating) {
            return back()->withErrors(__('legacy.error.error'));
        }

        (new DeleteMessageThreadAction())->execute($messageThread, $user);

        return redirect(route('message-thread.index'))->with('success', __('legacy.success.message_delete'));
    }
}
