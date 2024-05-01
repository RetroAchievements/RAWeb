<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use App\Models\Achievement;
use App\Platform\Requests\AchievementRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AchievementController extends Controller
{
    protected function resourceName(): string
    {
        return 'achievement';
    }

    public function index(): View
    {
        $this->authorize('viewAny', $this->resourceClass());

        return view('resource.index')
            ->with('resource', $this->resourceName());
    }

    public function show(Achievement $achievement, ?string $slug = null): View|RedirectResponse
    {
        $this->authorize('view', $achievement);

        if (!$this->resolvesToSlug($achievement->slug, $slug)) {
            return redirect($achievement->canonicalUrl);
        }

        $achievement->loadMissing([
            'game',
            'user',
        ]);

        return view($this->resourceName() . '.show')->with('achievement', $achievement);
    }

    public function edit(Achievement $achievement): View
    {
        $this->authorize('update', $achievement);

        $achievement->load([
            'game' => function ($query) {
                // $query->with('memoryNotes');
            },
            'user',
        ]);

        return view($this->resourceName() . '.edit')->with('achievement', $achievement);
    }

    public function update(AchievementRequest $request, Achievement $achievement): RedirectResponse
    {
        $this->authorize('update', $achievement);

        $achievement->fill($request->validated())->save();

        return back()->with('success', $this->resourceActionSuccessMessage('achievement', 'update'));
    }

    public function reportIssue(Achievement $achievement): View
    {
        return view('pages.achievement.[achievement].report-issue')->with('achievement', $achievement);
    }
}
