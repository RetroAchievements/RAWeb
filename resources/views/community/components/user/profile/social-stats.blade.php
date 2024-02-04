@props([
    'socialStats' => [],
])

<p role="heading" aria-level="2" class="mb-0.5 text-2xs font-bold">Social</p>
<div class="relative w-full p-2 bg-embed rounded">
    <x-user.profile.arranged-stat-items :stats="[$socialStats['forumPostsStat'], $socialStats['setsRequestedStat']]" />
</div>
