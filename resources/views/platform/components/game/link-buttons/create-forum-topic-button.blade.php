@props([
    'gameId' => 0,
])

<form
    action="/request/game/generate-forum-topic.php"
    method="POST"
    onsubmit="return confirm('Are you sure you want to create the official forum topic for this game?')"
>
    {!! csrf_field() !!}
    <input type="hidden" name="game" value="{{ $gameId }}">

    <button class="btn w-full py-2 block transition-transform lg:active:scale-[97%]">
        <span class="icon icon-md ml-1 mr-2">💬</span>
        Create New Forum Topic
    </button>
</form>
