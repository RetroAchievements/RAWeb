<?php

declare(strict_types=1);

namespace App\Site\Components;

use App\Site\Components\Concerns\DeferLoading;
use App\Support\Concerns\HandlesResources;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\Paginator;
use Livewire\Component;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\QueryBuilderRequest;

abstract class Grid extends Component
{
    use AuthorizesRequests;
    use DeferLoading;
    use HandlesResources;

    protected array $casts = [

    ];

    /**
     * @var array
     */
    protected $listeners = [
        // 'siteAdded' => '$refresh',
    ];

    protected array $pageSizes = [
        25,
        50,
        75,
        100,
    ];

    /** @var LengthAwarePaginator<Model>|null */
    protected ?LengthAwarePaginator $results = null;

    protected ?QueryBuilderRequest $request = null;

    public string $display = 'table';

    public bool $updateQuery = false;

    public array $filter = [];

    public array $page = [
        // 'size' => 10,
        // 'number' => 1,
    ];

    public string $search = '';

    public string $sort = '';

    public ?int $take = null;

    public function __construct()
    {
        /*
         * manually initialize trait-likes that are not in traits anymore
         */
        $this->initializeWithPagination();

        $this->fill(request()->only([
            'filter',
            'search',
            'sort',
        ]));
    }

    public function hydrate(): void
    {
    }

    public function render(): View
    {
        $this->loadDeferred();

        return view($this->view(), $this->viewData());
    }

    protected function view(): string
    {
        if (view()->exists('components.' . $this->resourceName() . '.grid')) {
            return 'components.' . $this->resourceName() . '.grid';
        }

        return 'components.resource.grid';
    }

    protected function viewData(): array
    {
        return [
            'columns' => $this->columns(),
            'pageSizes' => $this->pageSizes(),
            'resource' => $this->resourceName(),
            'request' => $this->request(),
            'results' => $this->results,
        ];
    }

    protected function allowedFilters(): iterable
    {
        return [];
    }

    protected function defaultSort(): array|string|AllowedSort
    {
        return '-id';
    }

    protected function allowedSorts(): iterable
    {
        return [];
    }

    // @phpstan-ignore-next-line
    protected function query(): Builder
    {
        $query = $this->resourceQuery();

        // ... do your thing

        return $query;
    }

    protected function authorizeGrid(): void
    {
        $this->authorize('viewAny', $this->resourceClass($this->resourceName()));
    }

    /**
     * @return LengthAwarePaginator<Model>|null
     */
    protected function load(): ?LengthAwarePaginator
    {
        if ($this->take) {
            $this->pageSizes = [$this->take];
            $this->pageSize($this->take);
        }

        if (!$this->resourceClass($this->resourceName())) {
            return null;
        }

        // call this before authorizing as it may initialize data needed to authorize
        $query = $this->query();

        $this->authorizeGrid();

        if ($this->search) {
            $searched = $this->resourceClass()::search($this->search);
            $query->whereIn('id', $searched->get()->pluck('id')->toArray());
        }

        $this->results = QueryBuilder::for($query, $this->request())
            ->defaultSort($this->defaultSort())
            ->allowedSorts($this->allowedSorts())
            ->allowedFilters($this->allowedFilters())
            ->paginate($this->request()->input('page.size'), ['*'], 'page.number');

        return $this->results;
    }

    public function getQueryString(): array
    {
        if ($this->take) {
            return [];
        }

        if (!$this->updateQuery) {
            return [];
        }

        return array_merge([
            'page' => ['except' => ['number' => '1', 'size' => $this->pageSizes[0]]],
            'filter' => ['except' => []],
            'search' => ['except' => ''],
            'sort' => ['except' => ''],
        ], $this->queryString);
    }

    public function initializeWithPagination(): void
    {
        // The "page" query string item should only be available
        // from within the original component mount run.
        $this->page = (array) request()->query('page');

        /*
         * sanitize inputs to defaults
         */
        $this->page['number'] ??= 1;
        if (!in_array($this->page['size'] ?? null, $this->pageSizes)) {
            $this->page['size'] = $this->pageSizes[0];
        }

        Paginator::currentPageResolver(fn () => $this->page['number']);

        Paginator::defaultView($this->paginationView());
    }

    public function paginationView(): string
    {
        return 'components.grid.pagination-links';
    }

    protected function columns(): iterable
    {
        return [];
    }

    protected function displayOptions(): iterable
    {
        return [
            'table',
        ];
    }

    protected function pageSizes(): iterable
    {
        if (!$this->results) {
            return [];
        }

        /**
         * only list page sizes that make sense in reference to the results total
         */
        $pageSizes = collect($this->pageSizes)
            ->filter(fn ($pageSize) => $pageSize <= $this->results->total() * 2);

        if ($pageSizes->count() === 1) {
            return [];
        }

        return $pageSizes->toArray();
    }

    public function pageSize(int $value): void
    {
        if (!in_array($value, $this->pageSizes)) {
            return;
        }
        /*
         * translate the current page size's page to the new page size to show as many currently displayed entries as possible
         */
        $this->page['number'] = floor(($this->page['number'] - 1) * $this->page['size'] / $value) + 1;
        $this->page['size'] = $value;
    }

    public function previousPage(): void
    {
        $this->page['number'] = (int) ($this->page['number'] - 1);
    }

    public function nextPage(): void
    {
        $this->page['number'] = (int) ($this->page['number'] + 1);
    }

    public function gotoPage(int $page): void
    {
        $this->page['number'] = $page;
    }

    public function resetPage(): void
    {
        $this->page['number'] = 1;
        $this->page['size'] = $this->pageSizes[0];
    }

    public function request(): QueryBuilderRequest
    {
        if (!$this->request) {
            $this->request = app(QueryBuilderRequest::class);

            /*
             * Forward component's values to the query builder request which passes them to the query builder.
             * This prevents it from reading from the current request which will contain the unchanged data due
             * to how LiveWire works.
             */
            $this->request->query->set('page', $this->page);
            $this->request->query->set('sort', $this->sort);
            $this->request->query->set('filter', $this->filter);
        }

        return $this->request;
    }

    public function updatingSearch(string $value): void
    {
        /*
         * reset pagination to first page if search term changed
         */
        if ($value !== $this->search) {
            $this->resetPage();
        }
    }

    public function updatingSort(string $value): void
    {
        /*
         * reset pagination to first page if sort changed
         */
        if ($value !== $this->sort) {
            $this->resetPage();
        }
    }

    public function sort(string $sortBy): void
    {
        /*
         * TODO: check the default initial sort by column
         */
        if (!empty($this->sort) && $sortBy === $this->sort) {
            $this->sort = $sortBy[0] === '-' ? ltrim($sortBy, '-') : '-' . $sortBy;
        } else {
            $this->resetPage();
            $this->sort = $sortBy;
        }
    }
}
