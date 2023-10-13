<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use App\Platform\Actions\LinkLatestEmulatorRelease;
use App\Platform\Models\Emulator;
use App\Platform\Models\EmulatorRelease;
use App\Platform\Requests\EmulatorReleaseRequest;
use App\Support\MediaLibrary\Actions\AddMediaAction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class EmulatorReleaseController extends Controller
{
    protected function resourceName(): string
    {
        return 'emulator.release';
    }

    public function index(Emulator $emulator): View
    {
        $this->authorize('viewAny', $this->resourceClass());

        $releases = $emulator->releases()->withTrashed()->orderByDesc('created_at')->get();
        $minimum = $emulator->releases()->stable()->minimum()->latest()->first();
        $stable = $emulator->releases()->stable()->latest()->first();
        $beta = $emulator->releases()->unstable()->latest()->first();

        if ($beta && $stable) {
            if (version_compare($beta->version, $stable->version, '<')) {
                $beta = null;
            }
        }

        return view('emulator.release.index')
            ->with('emulator', $emulator)
            ->with('releases', $releases)
            ->with('minimum', $minimum)
            ->with('stable', $stable)
            ->with('beta', $beta)
            ->with('resource', $this->resourceName());
    }

    public function create(Emulator $emulator): View
    {
        $this->authorize('create', $this->resourceClass());

        return view('emulator.release.create')
            ->with('emulator', $emulator);
    }

    public function store(
        EmulatorReleaseRequest $request,
        Emulator $emulator,
        AddMediaAction $addFileToCollectionAction,
        LinkLatestEmulatorRelease $linkLatestReleaseAction
    ): RedirectResponse {
        $this->authorize('create', $this->resourceClass());

        $data = $request->validated();
        $data['minimum'] ??= false;
        $data['stable'] ??= $data['minimum'] ?? false;
        $data['emulator_id'] = $emulator->id;
        /** @var EmulatorRelease $release */
        $release = EmulatorRelease::create($data);

        $addFileToCollectionAction->execute($release, $request, 'build_x86');
        $addFileToCollectionAction->execute($release, $request, 'build_x64');

        $linkLatestReleaseAction->execute($release->emulator);

        return redirect(route('emulator.release.edit', $release))
            ->with('success', $this->resourceActionSuccessMessage('emulator.release', 'create'));
    }

    public function show(EmulatorRelease $release): void
    {
        $this->authorize('view', $release);
    }

    public function edit(EmulatorRelease $release): View
    {
        $this->authorize('update', $release);

        return view('emulator.release.edit')
            ->with('release', $release)
            ->with('emulator', $release->emulator);
    }

    public function update(
        EmulatorReleaseRequest $request,
        EmulatorRelease $release,
        AddMediaAction $addFileToCollectionAction,
        LinkLatestEmulatorRelease $linkLatestReleaseAction
    ): RedirectResponse {
        $this->authorize('update', $release);

        $addFileToCollectionAction->execute($release, $request, 'build_x86');
        $addFileToCollectionAction->execute($release, $request, 'build_x64');

        $data = $request->validated();
        $data['minimum'] ??= false;
        $data['stable'] ??= $data['minimum'] ?? false;
        $release->fill($data)->save();

        $linkLatestReleaseAction
            ->execute($release->emulator);

        return back()->with('success', $this->resourceActionSuccessMessage('emulator.release', 'update'));
    }

    public function destroy(
        EmulatorRelease $release,
        LinkLatestEmulatorRelease $linkLatestReleaseAction
    ): RedirectResponse {
        $this->authorize('delete', $release);

        $emulator = $release->emulator;

        $release->delete();

        $linkLatestReleaseAction->execute($emulator);

        return redirect(route('emulator.release.index', $emulator))
            ->with('success', $this->resourceActionSuccessMessage('emulator.release', 'delete'));
    }

    public function forceDestroy(int $release): RedirectResponse
    {
        $release = EmulatorRelease::withTrashed()->find($release);

        abort_if($release === null, 404);

        $this->authorize('forceDelete', $release);

        $archives = $release->getMedia('build_x86');
        /** @var Media $archive */
        foreach ($archives as $archive) {
            $archive->delete();
        }

        $release->forceDelete();

        return redirect(route('emulator.release.index', $release->emulator))->with(
            'success',
            $this->resourceActionSuccessMessage('emulator.release', 'delete')
        );
    }

    public function restore(int $release, LinkLatestEmulatorRelease $linkLatestReleaseAction): RedirectResponse
    {
        $release = EmulatorRelease::withTrashed()->find($release);

        abort_if($release === null, 404);

        $this->authorize('restore', $release);

        $release->restore();

        $linkLatestReleaseAction->execute($release->emulator);

        return redirect(route('emulator.release.edit', $release))->with(
            'success',
            $this->resourceActionSuccessMessage('emulator.release', 'restore')
        );
    }
}
