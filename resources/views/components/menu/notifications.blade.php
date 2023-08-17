@auth
    {{--<li class="nav-item">
        <a class="nav-link" href="{{ route('activity') }}" title="{{ __('Activity') }}" data-toggle="tooltip">
            <x-fas-bolt />
        </a>
    </li>--}}
    <livewire:notification-icon class="{{$class ?? ''}}" />
    <livewire:message-icon class="{{$class ?? ''}}" />
@endauth
