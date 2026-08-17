<?php

// Temporary QA page to demo the ticket list backend functionality.
// This will be deleted in favor of a React page.
// This code is pretty messy, but this is a temporary file.

use App\Models\Achievement;
use App\Models\Game;
use App\Platform\Actions\BuildTicketListAction;
use App\Platform\Enums\TicketListScope;
use App\Platform\Enums\TicketListSortField;
use App\Platform\Enums\TicketListStatusFilter;
use App\Platform\Requests\TicketListApiRequest;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:root']);
name('demo.ticket-list');

render(function (View $view) {
    $request = TicketListApiRequest::createFrom(request());
    $scope = TicketListScope::from((string) $request->query('scope', TicketListScope::All->value));

    $action = app(BuildTicketListAction::class);

    $hasRequiredTarget = $scope === TicketListScope::All || $request->filled(match ($scope) {
        TicketListScope::Game => 'game',
        TicketListScope::Achievement => 'achievement',
        default => 'user',
    });
    $target = $hasRequiredTarget ? $scope->resolveTarget($request) : null;
    $result = $hasRequiredTarget ? $action->execute($scope, $target, $request) : null;

    $systemId = match (true) {
        $target instanceof Game => $target->system_id,
        $target instanceof Achievement => $target->game->system_id,
        default => null,
    };

    return $view->with([
        'result' => $result,
        'availableFilters' => $action->getAvailableFilters($scope, $systemId),
        'scopes' => TicketListScope::cases(),
        'statuses' => TicketListStatusFilter::cases(),
        'defaultStatus' => $scope->defaultStatusFilter()->value,
        'sortFields' => TicketListSortField::cases(),
        'apiUrl' => route('api.ticket.index', request()->query()),
    ]);
});

?>

<x-app-layout pageTitle="Ticket List Demo">
    <p class="mb-8">This page is just here to prove the back-end stuff works. Pay no attention to this page's code. This file is going to be deleted.</p>

    <div class="flex flex-col gap-4">
        <form method="GET" action="" class="flex flex-wrap items-end gap-3" x-data="{ scope: '{{ request('scope', 'all') }}' }">
            <label class="flex flex-col">Scope
                <select name="scope" class="px-2 py-1 border" x-model="scope" @change="$el.form.requestSubmit()">
                    @foreach ($scopes as $case)
                        <option value="{{ $case->value }}" @selected(request('scope', 'all') === $case->value)>{{ $case->value }}</option>
                    @endforeach
                </select>
            </label>
            <label class="flex flex-col" x-show="scope === 'game'" x-cloak>Game id<input type="number" name="game" value="{{ request('game') }}" class="px-2 py-1 border" :disabled="scope !== 'game'" /></label>
            <label class="flex flex-col" x-show="scope === 'achievement'" x-cloak>Achievement id<input type="number" name="achievement" value="{{ request('achievement') }}" class="px-2 py-1 border" :disabled="scope !== 'achievement'" /></label>
            <label class="flex flex-col" x-show="['assignedTo', 'reportedBy', 'awaitingReporter', 'resolvedBy'].includes(scope)" x-cloak>User id<input type="number" name="user" value="{{ request('user') }}" class="px-2 py-1 border" :disabled="!['assignedTo', 'reportedBy', 'awaitingReporter', 'resolvedBy'].includes(scope)" /></label>
            <label class="flex flex-col">Status
                <select name="filter[status]" class="px-2 py-1 border">
                    @foreach ($statuses as $case)
                        <option value="{{ $case->value }}" @selected(request('filter.status', $defaultStatus) === $case->value)>{{ $case->value }}</option>
                    @endforeach
                </select>
            </label>
            <label class="flex flex-col">Sort
                <select name="sort" class="px-2 py-1 border">
                    @foreach ($sortFields as $case)
                        <option value="-{{ $case->value }}" @selected(request('sort', '-createdAt') === "-{$case->value}")>-{{ $case->value }}</option>
                        <option value="{{ $case->value }}" @selected(request('sort') === $case->value)>{{ $case->value }}</option>
                    @endforeach
                </select>
            </label>
            @foreach ($availableFilters as $filter)
                <label class="flex flex-col">{{ $filter->kind->value }}
                    <select name="filter[{{ $filter->kind->value }}]" class="px-2 py-1 border">
                        @foreach ($filter->values as $value)
                            <option value="{{ $value }}" @selected((string) request("filter.{$filter->kind->value}", $filter->values[0]) === (string) $value)>{{ $value }}</option>
                        @endforeach
                    </select>
                </label>
            @endforeach
            <label class="flex flex-col">Page<input type="number" name="page[number]" value="{{ request('page.number', 1) }}" min="1" class="px-2 py-1 border" /></label>
            <button type="submit" class="px-3 py-1 btn">Load</button>
        </form>

        @if ($result)
            <p><a href="{{ $apiUrl }}">Open this query on the internal API route (TicketApiController)</a></p>

            @dump($result)
        @endif
    </div>
</x-app-layout>
