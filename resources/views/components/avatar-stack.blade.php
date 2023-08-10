@props(['usernames' => []])

@foreach ($usernames as $username)
    <img
        src="{{ media_asset('/UserPic/' . $username . '.png') }}"
        class="rounded-full w-7 h-7 outline outline-2 outline-embed-highlight" 
        width="28" 
        height="28"
    >
@endforeach