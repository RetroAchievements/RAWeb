<?php
use Illuminate\Support\Carbon;

$isNewAccount = false;
if (Auth::user()) {
    $isNewAccount = Carbon::now()->diffInMonths(Auth::user()->Created) < 1;
}
?>
<x-app-layout>
    @guest
        @include('content.welcome')
    @elseif($isNewAccount)
        @include('content.getting-started')
    @endguest

    <x-news.carousel-2 />
    <x-claims.finished-claims count="6" />

    <?php
        RenderActivePlayersComponent();
    ?>

    <x-user.online-count-chart />
    <x-claims.new-claims count="5" />

    <?php
        RenderRecentForumPostsComponent();
    ?>

    @slot('sidebar')
        @include('content.top-links')

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
    @endslot
</x-app-layout>
