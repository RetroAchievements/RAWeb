<x-app-layout>
    @guest
        @include('content.welcome')
    @endguest

    <x-news.carousel />
    <x-claims.finished-claims count="6" />

    @php
        RenderActivePlayersComponent();
    @endphp

    <x-user.online-count-chart />
    <x-claims.new-claims count="5" />

    @php
        RenderRecentForumPostsComponent();
    @endphp

    @slot('sidebar')
        @include('content.top-links')
        <x-event.aotw />
        @include('content.static-data')
    @endslot
</x-app-layout>
