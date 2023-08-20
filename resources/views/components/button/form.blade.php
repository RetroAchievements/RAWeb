<form class="inline-block" action="{{ $action }}" method="post" onsubmit="return confirm('{{ $confirm ?? __('Are you sure?') }}')">
    @csrf
    @method($method ?? 'post')
    <button class="{{ $class ?? 'btn' }}" data-toggle="tooltip" title="{{ $title ?? null }}">{{ $slot }}</button>
</form>
