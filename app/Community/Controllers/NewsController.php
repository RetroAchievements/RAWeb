<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Requests\NewsRequest;
use App\Http\Controller;
use App\Models\News;
use App\Support\MediaLibrary\Actions\AddMediaAction;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class NewsController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', News::class);

        return view('news.index');
    }

    public function create(): View
    {
        $this->authorize('store', News::class);

        return view('news.create');
    }

    public function store(NewsRequest $request, AddMediaAction $addMediaAction): RedirectResponse
    {
        $this->authorize('store', News::class);

        $data = $request->validated();
        $data['sticky'] ??= false;

        /** @var News $news */
        $news = News::create($data);

        $addMediaAction->execute($news, $request, 'image');

        return redirect(route('news.show', $news))
            ->with('success', $this->resourceActionSuccessMessage('news', 'create'));
    }

    public function show(News $news, ?string $slug = null): View|RedirectResponse
    {
        $this->authorize('view', $news);

        if (!$this->resolvesToSlug($news->slug, $slug)) {
            return redirect(route('news.show', [$news, $news->slug]));
        }

        return view('news.show')
            ->with('news', $news);
    }

    public function edit(News $news): View
    {
        $this->authorize('update', $news);

        return view('news.edit')
            ->with('news', $news);
    }

    public function update(
        NewsRequest $request,
        News $news,
        AddMediaAction $addMediaAction
    ): RedirectResponse {
        $this->authorize('update', $news);

        $addMediaAction->execute($news, $request, 'image');

        $news->fill($request->validated())->save();

        return redirect(route('news.edit', $news))
            ->with('success', $this->resourceActionSuccessMessage('news', 'update'));
    }

    public function destroy(News $news): void
    {
        $this->authorize('delete', $news);
    }

    public function destroyImage(News $news): RedirectResponse
    {
        $this->authorize('deleteImage', $news);

        $news->clearMediaCollection('image');

        return redirect(route('news.edit', $news))
            ->with('success', $this->resourceActionSuccessMessage('news.image', 'delete'));
    }
}
