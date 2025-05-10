@use('App\Models\Game')
@use('App\Models\User')

@php
    $games = Game::search('poke')->get();
    $users = User::search('scott')->get();
@endphp

<x-app-layout>
    <div className="flex flex-col gap-6">
        <p>Games</p>
        @dump($games->toArray())

        <p class="mt-8">Users</p>
        @dump($users->toArray())
    </div>
</x-app-layout>
