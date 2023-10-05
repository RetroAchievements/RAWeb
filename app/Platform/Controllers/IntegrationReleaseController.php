<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use App\Platform\Actions\LinkLatestIntegrationRelease;
use App\Platform\Models\IntegrationRelease;
use App\Platform\Requests\IntegrationReleaseRequest;
use App\Support\MediaLibrary\Actions\AddMediaAction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class IntegrationReleaseController extends Controller
{
    protected function resourceName(): string
    {
        return 'integration.release';
    }

    public function index(): View
    {
        $this->authorize('viewAny', $this->resourceClass());

        $releases = IntegrationRelease::withTrashed()->orderByDesc('created_at')->get();
        $minimum = IntegrationRelease::stable()->minimum()->latest()->first();
        $stable = IntegrationRelease::stable()->latest()->first();
        $beta = IntegrationRelease::latest()->first();

        return view($this->resourceName() . '.index')
            ->with('releases', $releases)
            ->with('minimum', $minimum)
            ->with('stable', $stable)
            ->with('beta', $beta)
            ->with('resource', $this->resourceName());
    }

    public function create(): View
    {
        $this->authorize('create', $this->resourceClass());

        return view('integration.release.create');
    }

    public function store(
        IntegrationReleaseRequest $request,
        AddMediaAction $addMediaAction,
        LinkLatestIntegrationRelease $linkLatestReleaseAction
    ): RedirectResponse {
        $this->authorize('create', $this->resourceClass());

        $data = $request->validated();
        $data['minimum'] ??= false;
        $data['stable'] ??= $data['minimum'] ?? false;
        /** @var IntegrationRelease $release */
        $release = IntegrationRelease::create($data);

        $addMediaAction->execute($release, $request, 'build_x86');
        $addMediaAction->execute($release, $request, 'build_x64');
        $linkLatestReleaseAction->execute();

        return redirect(route('integration.release.edit', $release))
            ->with('success', $this->resourceActionSuccessMessage('integration.release', 'create'));
    }

    public function show(IntegrationRelease $release): void
    {
        $this->authorize('view', $release);
    }

    public function edit(IntegrationRelease $release): View
    {
        $this->authorize('update', $release);

        return view('integration.release.edit')
            ->with('release', $release);
    }

    public function update(
        IntegrationReleaseRequest $request,
        IntegrationRelease $release,
        AddMediaAction $addMediaAction,
        LinkLatestIntegrationRelease $linkLatestReleaseAction
    ): RedirectResponse {
        $this->authorize('update', $release);

        $data = $request->validated();
        $data['minimum'] ??= false;
        $data['stable'] ??= $data['minimum'] ?? false;
        $release->fill($data)->save();

        $addMediaAction->execute($release, $request, 'build_x86');
        $addMediaAction->execute($release, $request, 'build_x64');
        $linkLatestReleaseAction->execute();

        return back()->with('success', $this->resourceActionSuccessMessage('integration.release', 'update'));
    }

    public function destroy(
        IntegrationRelease $release,
        LinkLatestIntegrationRelease $linkLatestReleaseAction
    ): RedirectResponse {
        $this->authorize('delete', $release);

        $release->delete();

        $linkLatestReleaseAction->execute();

        return redirect(route('integration.release.index'))
            ->with('success', $this->resourceActionSuccessMessage('integration.release', 'delete'));
    }

    public function forceDestroy(int $release): RedirectResponse
    {
        $this->authorize('forceDelete', $release);

        $release = IntegrationRelease::withTrashed()->find($release);

        abort_if($release === null, 404);

        $this->authorize('forceDelete', $release);

        $builds = $release->getMedia('build_x86');
        /** @var Media $build */
        foreach ($builds as $build) {
            $build->delete();
        }

        $release->forceDelete();

        return redirect(route('integration.release.index'))
            ->with('success', $this->resourceActionSuccessMessage('integration.release', 'delete'));
    }

    public function restore(int $release, LinkLatestIntegrationRelease $linkLatestReleaseAction): RedirectResponse
    {
        $release = IntegrationRelease::withTrashed()->find($release);

        abort_if($release === null, 404);

        $this->authorize('restore', $release);

        $release->restore();

        $linkLatestReleaseAction->execute();

        return redirect(route('integration.release.index'))
            ->with('success', $this->resourceActionSuccessMessage('integration.release', 'restore'));
    }
}
