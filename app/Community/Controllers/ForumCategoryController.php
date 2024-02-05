<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Requests\ForumCategoryRequest;
use App\Http\Controller;
use App\Models\ForumCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ForumCategoryController extends Controller
{
    public function index(): RedirectResponse
    {
        /*
         * redirect to community forum category
         */
        return redirect(route('forum-category.show', 1));
    }

    public function create(): void
    {
        // TODO
        dd('implement');
    }

    public function store(ForumCategoryRequest $request, ForumCategory $category): void
    {
        // TODO
        dd('implement');
    }

    public function show(ForumCategory $category, ?string $slug = null): View|RedirectResponse
    {
        if (!$this->resolvesToSlug($category->slug, $slug)) {
            return redirect($category->canonicalUrl);
        }

        $forums = $category->forums()
            ->orderBy('order_column')
            ->withCount(['topics', 'comments'])
            ->paginate();

        return view('forum-category.show')
            ->with('category', $category)
            ->with('forums', $forums);
    }

    public function edit(ForumCategory $category): View
    {
        return view('forum-category.edit')
            ->with('category', $category);
    }

    public function update(ForumCategoryRequest $request, ForumCategory $category): RedirectResponse
    {
        $category->fill($request->validated())->save();

        return back()->with('success', $this->resourceActionSuccessMessage('forum-category', 'update'));
    }

    public function destroy(ForumCategory $category): void
    {
        // TODO
        dd('implement');
    }
}
