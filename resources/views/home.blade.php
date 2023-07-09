<x-app-layout>
    @guest
        @include('content.welcome')
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

        @if(isset($eventAchievement))
            <x-event.aotw
                :achievement="$eventAchievement"
                :game="$eventGame"
                :consoleName="$eventConsoleName"
                :forumTopicId="$eventForumTopicId"
            />
        @endif

        @include('content.static-data')
    @endslot
</x-app-layout>
