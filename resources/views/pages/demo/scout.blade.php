@use('App\Models\Game')
@use('App\Models\User')

@php
    // Get search terms from query params or use defaults.
    $gameSearch = request()->query('game_search', 'poke');
    $userSearch = request()->query('user_search', 'scott');
    
    // Search using the terms (default or provided).
    $games = Game::search($gameSearch)->get();
    $users = User::search($userSearch)->get();
@endphp

<x-app-layout>
    <div class="flex flex-col gap-6">
        <form method="GET" action="" class="flex gap-4 mb-4">
            <div>
                <label for="game_search" class="block text-sm mb-1">Game Search</label>
                <input 
                    type="text" 
                    id="game_search"
                    name="game_search" 
                    value="{{ $gameSearch }}" 
                    placeholder="Search games"
                    class="rounded border px-2 py-1"
                >
            </div>
            
            <div>
                <label for="user_search" class="block text-sm mb-1">User Search</label>
                <input 
                    type="text" 
                    id="user_search"
                    name="user_search" 
                    value="{{ $userSearch }}" 
                    placeholder="Search users"
                    class="rounded border px-2 py-1"
                >
            </div>
            
            <button 
                type="submit" 
                class="bg-blue-500 text-white px-4 py-1 rounded self-end"
            >
                Search
            </button>
        </form>
        
        <p>Games</p>
        @dump($games->toArray())

        <p class="mt-8">Users</p>
        @dump($users->toArray())
    </div>
</x-app-layout>
