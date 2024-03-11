@props([
    'totalBeatenHardcoreCount' => 0,
    'totalBeatenSoftcoreCount' => 0,
    'totalCompletedCount' => 0,
    'totalMasteredCount' => 0,
])

<div class="{{ ($totalCompletedCount || $totalBeatenSoftcoreCount) ? 'grid grid-cols-2' : 'flex' }} gap-x-4 [&>div]:flex [&>div]:items-center [&>div]:gap-x-1 sm:flex sm:gap-x-3 md:gap-x-4">
    <div class="order-1">
        <p class="text-zinc-500 font-bold text-xs">Unfinished</p>
    </div>

    @if ($totalBeatenSoftcoreCount > 0)
        <div class="order-3 sm:order-2">
            <div class="rounded-full w-2 h-2 border border-zinc-400"></div>
            <p class="text-zinc-400 font-bold text-xs">Beaten Softcore</p>
        </div>
    @endif

    @if ($totalBeatenHardcoreCount > 0 || ($totalBeatenHardcoreCount === 0 && $totalBeatenSoftcoreCount === 0))
        <div class="order-5 sm:order-3">
            <div class="rounded-full w-2 h-2 bg-zinc-300"></div>
            <p class="text-zinc-300 font-bold text-xs">Beaten</p>
        </div>
    @endif

    @if ($totalCompletedCount > 0)
        <div class="order-2 sm:order-4">
            <div class="rounded-full w-2 h-2 border border-yellow-600"></div>
            <p class="text-yellow-600 font-bold text-xs">Completed</p>
        </div>
    @endif

    @if ($totalMasteredCount > 0 || ($totalMasteredCount === 0 && $totalCompletedCount === 0))
        <div class="order-4 sm:order-5">
            <div class="rounded-full w-2 h-2 bg-yellow-600"></div>
            <p class="text-[gold] light:text-yellow-600 font-bold text-xs">Mastered</p>
        </div>
    @endif
</div>