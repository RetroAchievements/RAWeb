<ul class="navbar-nav">
    <x-nav-dropdown dropdown-class="dropdown-menu-right">
        <x-slot name="trigger">
            <x-fas-toolbox/>
            {{--<span class="hidden xl:inline-block">Manage</span>--}}
            <span class="sr-only">Manage</span>
        </x-slot>
        @can('manage', \App\Community\Models\TriggerTicket::class)
            <h6 class="dropdown-header">Tickets</h6>
            <a class="dropdown-item" href="{{ route('triggers.ticket.index') }}">
                Tickets
            </a>
            <div class="dropdown-divider"></div>
        @endcan
        @can('manage', App\Community\Models\Forum::class)
            {{--<h6 class="dropdown-header">Forums</h6>
            <a class="dropdown-item" href="{{ route('forum-topic.clearing') }}?f=0">
                Forum Clearing
            </a>
            <div class="dropdown-divider"></div>--}}
        @endcan
        @can('manage', App\Community\Models\News::class)
            <h6 class="dropdown-header">News</h6>
            <a class="dropdown-item" href="{{ route('news.index') }}">News</a>
            <div class="dropdown-divider"></div>
        @endcan
        {{--@if(request()->user()->role_id >= \RA\Site\Models\Role::Admin)
            <h6 class="dropdown-header">Administration</h6>
            <a class="dropdown-item" href="{{ route('admin.dashboard') }}">Admin</a>
            <div class="dropdown-divider"></div>
        @endif
        @can('manage', RA\Site\Models\Event::class)
            <h6 class="dropdown-header">Events</h6>
            <div class="dropdown-divider"></div>
        @endcan--}}
        @can('manage', App\Platform\Models\IntegrationRelease::class)
            <h6 class="dropdown-header">Releases</h6>
            @can('manage', App\Platform\Models\Emulator::class)
                <a class="dropdown-item {{ request()->routeIs('emulator*') ? 'active' : '' }}"
                   href="{{ route('emulator.index') }}">
                    Emulators
                </a>
            @endcan
            <a class="dropdown-item {{ request()->routeIs('integration.release*') ? 'active' : '' }}"
               href="{{ route('integration.release.index') }}">
                Integration
            </a>
            <div class="dropdown-divider"></div>
        @endcan
        @can('root')
            <h6 class="dropdown-header">System Tools</h6>
            @can('viewHorizon')
                <a class="dropdown-item" href="{{ url('horizon') }}" target="_blank">
                    Horizon Queue
                    <x-fas-external-link-alt/>
                </a>
            @endcan
            @can('viewLogs')
                <a class="dropdown-item" href="{{ url('log-viewer') }}" target="_blank">
                    Logs
                    <x-fas-external-link-alt/>
                </a>
            @endcan
            @can('viewRouteUsage')
                <a class="dropdown-item" href="{{ url('route-usage') }}" target="_blank">
                    Route Usage
                    <x-fas-external-link-alt/>
                </a>
            @endcan
            @can('viewSyncStatus')
                <a class="dropdown-item" href="{{ route('sync-status') }}">
                    Sync Status
                </a>
            @endcan
            @can('viewWebSocketsDashboard')
                <a class="dropdown-item" href="{{ url('websockets') }}" target="_blank">
                    Web Sockets
                    <x-fas-external-link-alt/>
                </a>
            @endcan
        @endcan
    </x-nav-dropdown>
</ul>
