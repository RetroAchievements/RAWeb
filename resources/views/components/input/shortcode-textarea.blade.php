@props([
    'name' => 'body',
    'enabled' => true,
    'placeholder' => '',
    'maxLength' => 60000,
])

<?php

$inputEnabled = $enabled ? '' : 'disabled';
$buttonEnabled = $enabled ? ":disabled='!isValid'" : "disabled";

?>

<div>
    <div>
        {!! RenderShortcodeButtons(); !!}
    </div>

    <script>
    function disableRepost() {
        var btn = $('#postBtn');
        btn.attr('disabled', true);
        btn.html('Sending...');
    }
    </script>

    <textarea
        id="commentTextarea"
        class="w-full mb-2"
        rows="10" cols="63"
        {!! $inputEnabled !!}
        maxlength="{{ $maxLength }}"
        name="{{ $name }}"
        placeholder="{{ $placeholder }}"
        x-on:input="autoExpandTextInput($el); isValid = window.getStringByteCount($event.target.value) <= {{ $maxLength }};"
    >{{ $slot }}</textarea>

    <div class="flex justify-between mb-2">
        <span class="textarea-counter" data-textarea-id="commentTextarea">0 / 60000</span>

        <div>
            <x-fas-spinner id="preview-loading-icon" class="animate-spin opacity-0 transition-all duration-200" aria-hidden="true" />
            <button id="preview-button" type="button" class="btn" onclick="window.loadPostPreview()" {!! $buttonEnabled !!}>Preview</button>
            <button id="postBtn" class="btn" onclick="this.form.submit(); disableRepost();" {!! $buttonEnabled !!}>Submit</button>
        </div>
    </div>

    <div id='post-preview'></div>
<div>
