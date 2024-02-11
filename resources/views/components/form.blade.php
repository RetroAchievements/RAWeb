@props([
    'action' => null,
    'method' => 'post',
    'validate' => false,
    'enctype' => 'multipart/form-data',
])

<form
    action="{{ $action }}"
    method="{{ $method }}"
    enctype="{{ $enctype }}"
    x-data="{ isValid: {{ $validate ? 'false' : 'true' }}, isSending: false }"
    x-on:submit="isSending = true"
    {{ $attributes }}
>
    @csrf
    @method($method)
    {{ $slot }}
</form>
