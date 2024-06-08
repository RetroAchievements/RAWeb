@props([
    'achievementCount' => 0,
])

<div class="place-content-center grid grid-cols-[repeat(auto-fill,minmax(52px,52px))] px-0.5 sm:px-4">
    @for ($i = 0; $i < $achievementCount; $i++)
        <div class="w-[52px] h-[52px] bg-stone-800 light:bg-stone-300"></div>
    @endfor
</div>
