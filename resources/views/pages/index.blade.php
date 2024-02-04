<?php
use Illuminate\Support\Carbon;
?>
<x-app-layout>
    @guest
        <x-content.welcome />
    @elseif(auth()->user()?->isNew())
        <x-content.getting-started />
    @endguest

    <x-news.carousel-2 />
    <x-claims.finished-claims count="6" />

    <x-active-players />

    <div class="mb-8">
        <x-user.online-count-chart />
    </div>

    <x-claims.new-claims count="5" />
    <x-forum-recent-posts />

    <x-slot name="sidebar">
        <x-content.top-links />
        <div class="mt-6 flex flex-col gap-y-3">
            @if(isset($eventAchievement))
                <x-event.aotw
                    :achievement="$eventAchievement"
                    :game="$eventGame"
                    :consoleName="$eventConsoleName"
                    :forumTopicId="$eventForumTopicId"
                />
            @endif
            <x-global-statistics />
        </div>
    </x-slot>
</x-app-layout>
