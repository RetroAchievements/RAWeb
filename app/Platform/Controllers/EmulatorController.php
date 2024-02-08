<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use App\Models\Emulator;
use App\Platform\Requests\EmulatorRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmulatorController extends Controller
{
    protected function resourceName(): string
    {
        return 'emulator';
    }

    public function index(): View
    {
        $this->authorize('viewAny', $this->resourceClass());

        return view('resource.index')
            ->with('resource', $this->resourceName());
    }

    public function create(): void
    {
        $this->authorize('create', $this->resourceClass());
    }

    public function store(Request $request): void
    {
        $this->authorize('create', $this->resourceClass());
    }

    public function show(Emulator $emulator): void
    {
        $this->authorize('view', $emulator);
    }

    public function edit(Emulator $emulator): View
    {
        $this->authorize('update', $emulator);

        $emulator->loadMissing([
            'systems' => function ($query) {
                $query->orderBy('name');
            },
            'latestRelease',
        ]);

        $emulator->loadCount(['releases', 'systems']);

        return view('emulator.edit')
            ->with('emulator', $emulator);
    }

    public function update(EmulatorRequest $request, Emulator $emulator): RedirectResponse
    {
        $this->authorize('update', $emulator);

        $data = $request->validated();
        $data['active'] ??= false;

        $emulator->fill($data)->save();

        return redirect(route('emulator.edit', $emulator))
            ->with('success', $this->resourceActionSuccessMessage('emulator', 'update'));
    }

    public function destroy(Emulator $emulator): void
    {
        $this->authorize('delete', $emulator);
    }
}
