@auth
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" href="{{ route('activity') }}" title="{{ __('Activity') }}" data-toggle="tooltip">
                <x-fas-bolt />
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('notification.index') }}" title="{{ __res('notification') }}" data-toggle="tooltip">
                <livewire:notification-icon />
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('message.index') }}" title="{{ __res('message') }}" data-toggle="tooltip">
                <livewire:message-icon />
            </a>
        </li>
    </ul>
@endauth
