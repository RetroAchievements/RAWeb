<div>
    <button type="button" class="btn btn-secondary" onclick="injectShortcode('{{ $id ?? 'message' }}', '[b]', '[/b]')"
            data-toggle="tooltip" title="{{ __('Bold') }}">
        <x-fas-bold />
    </button>
    <button type="button" class="btn btn-secondary" onclick="injectShortcode('{{ $id ?? 'message' }}', '[i]', '[/i]')"
            data-toggle="tooltip" title="{{ __('Italic') }}">
        <x-fas-italic />
    </button>
    <button type="button" class="btn btn-secondary" onclick="injectShortcode('{{ $id ?? 'message' }}', '[s]', '[/s]')"
            data-toggle="tooltip" title="{{ __('Strikethrough') }}">
        <x-fas-strikethrough />
    </button>
</div>
<div>
    <button type="button" class="btn btn-secondary" onclick="injectShortcode('{{ $id ?? 'message' }}', '[img=', ']')"
            data-toggle="tooltip" title="{{ __('Image') }}">
        <x-fas-image />
    </button>
    <button type="button" class="btn btn-secondary" onclick="injectShortcode('{{ $id ?? 'message' }}', '[url=', ']Link[/url]')"
            data-toggle="tooltip" title="{{ __('Link') }}">
        <x-fas-link />
    </button>
    <button type="button" class="btn btn-secondary" onclick="injectShortcode('{{ $id ?? 'message' }}', '[ach=', ']')"
            data-toggle="tooltip" title="{{ __('Achievement') }}">
        <x-fas-trophy />
    </button>
    <button type="button" class="btn btn-secondary" onclick="injectShortcode('{{ $id ?? 'message' }}', '[game=', ']')"
            data-toggle="tooltip" title="{{ __('Game') }}">
        <x-fas-gamepad />
    </button>
    <button type="button" class="btn btn-secondary" onclick="injectShortcode('{{ $id ?? 'message' }}', '[user=', ']')"
            data-toggle="tooltip" title="{{ __('User') }}">
        <x-fas-user />
    </button>
</div>
