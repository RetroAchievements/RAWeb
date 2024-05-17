<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use App\Models\System;
use App\Platform\Requests\SystemRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    protected function resourceName(): string
    {
        return 'system';
    }

    public function index(): View
    {
        $this->authorize('viewAny', $this->resourceClass());

        return view('resource.index')
            ->with('resource', $this->resourceName());
    }

    public function create(): void
    {
    }

    public function store(Request $request): void
    {
    }

    public function show(System $system, ?string $slug = null): View|RedirectResponse
    {
        $this->authorize('view', $system);

        if (!$this->resolvesToSlug($system->slug, $slug)) {
            return redirect($system->canonicalUrl);
        }

        /** @var System $system */
        $system = $system->withCount(['games', 'achievements', 'emulators'])->find($system->id);
        $system->load([
            'emulators' => function ($query) {
                $query->orderBy('name');
            },
        ]);
        $games = $system->games()->orderBy('updated_at')->take(5)->get();

        return view('system.show')
            ->with('system', $system)
            ->with('games', $games);
    }

    public function edit(System $system): View
    {
        $this->authorize('update', $system);

        return view($this->resourceName() . '.edit')->with('system', $system);
    }

    public function update(SystemRequest $request, System $system): RedirectResponse
    {
        $this->authorize('update', $system);

        $system->fill($request->validated())->save();

        return back()->with('success', $this->resourceActionSuccessMessage('system', 'update'));
    }

    public function destroy(System $system): void
    {
    }
}
