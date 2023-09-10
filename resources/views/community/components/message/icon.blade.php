<div class="navitem {{ $class ?? '' }}">
    {{--<a class="nav-link" href="{{ route('message.index') }}" title="{{ __res('message') }}" data-toggle="tooltip">--}}
    <a class="nav-link" href="{{ url('inbox.php') }}" title="{{ __res('message') }}" data-toggle="tooltip">
        <x-fas-envelope />
        @if($count ?? 0)
            <div class="text-danger absolute translate-x-3 -translate-y-1 text-[8px]">
                <x-fas-circle />
            </div>
        @endif
    </a>
</div>
