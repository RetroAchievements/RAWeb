<?php

declare(strict_types=1);

namespace App\Platform\Controllers\Demo;

use App\Http\Controller;
use App\Models\GameScreenshot;
use App\Models\Role;
use App\Platform\Actions\ApproveGameScreenshotAction;
use App\Platform\Actions\RejectGameScreenshotAction;
use App\Platform\Enums\GameScreenshotRejectionReason;
use App\Platform\Enums\GameScreenshotStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class GameScreenshotModerationDemoController extends Controller
{
    public function index(): View
    {
        $this->abortUnlessRoot();

        $pendingScreenshots = GameScreenshot::query()
            ->with(['capturedBy', 'game', 'media'])
            ->where('status', GameScreenshotStatus::Pending)
            ->latest('id')
            ->get();

        $rejectionReasons = collect(GameScreenshotRejectionReason::cases())
            ->mapWithKeys(fn (GameScreenshotRejectionReason $reason) => [$reason->value => $reason->label()]);

        return view('demo.game-screenshot-moderation', [
            'pendingScreenshots' => $pendingScreenshots,
            'queueConnectionName' => config('media-library.queue_connection_name'),
            'queueConversionsByDefault' => config('media-library.queue_conversions_by_default'),
            'rejectionReasons' => $rejectionReasons,
        ]);
    }

    public function approve(Request $request, GameScreenshot $gameScreenshot): RedirectResponse
    {
        $this->abortUnlessRoot();

        if ($gameScreenshot->status !== GameScreenshotStatus::Pending) {
            return back()->with('error', "Screenshot #{$gameScreenshot->id} is not pending.");
        }

        try {
            (new ApproveGameScreenshotAction())->execute($gameScreenshot, $request->user());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('demo.game-screenshot-moderation.index')
            ->with('success', "Approved screenshot #{$gameScreenshot->id}.");
    }

    public function reject(Request $request, GameScreenshot $gameScreenshot): RedirectResponse
    {
        $this->abortUnlessRoot();

        if ($gameScreenshot->status !== GameScreenshotStatus::Pending) {
            return back()->with('error', "Screenshot #{$gameScreenshot->id} is not pending.");
        }

        $validated = $request->validate([
            'reason' => ['required', new Enum(GameScreenshotRejectionReason::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        (new RejectGameScreenshotAction())->execute(
            $gameScreenshot,
            $request->user(),
            GameScreenshotRejectionReason::from($validated['reason']),
            $validated['notes'] ?: null,
        );

        return redirect()
            ->route('demo.game-screenshot-moderation.index')
            ->with('success', "Rejected screenshot #{$gameScreenshot->id}.");
    }

    private function abortUnlessRoot(): void
    {
        abort_unless(request()->user()?->hasRole(Role::ROOT), 403);
    }
}
