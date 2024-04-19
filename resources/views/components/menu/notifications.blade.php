@auth
    {{--<li class="nav-item">
        <a class="nav-link" href="{{ route('activity') }}" title="{{ __('Activity') }}" data-toggle="tooltip">
            <x-fas-bolt />
        </a>
    </li>--}}
    @can('manage', App\Models\Ticket::class)
        <livewire:ticket-notifications-icon class="{{$class ?? ''}}" />
    @endcan
    <livewire:general-notifications-icon class="{{$class ?? ''}}" />
    {{--<livewire:message-icon class="{{$class ?? ''}}" />--}}
@endauth
