@use('App\Models\Comment')
@use('App\Models\ForumTopicComment')
@use('App\Models\Game')
@use('App\Models\GameSet')
@use('App\Models\User')
@use('Illuminate\Support\Facades\Auth')

@php
    $me = Auth::user();
    abort_if(!$me || !$me->hasRole(\App\Models\Role::ADMINISTRATOR), 404);

    // Get search terms from query params.
    $gameSearch = request()->query('game_search');
    $userSearch = request()->query('user_search');
    $commentSearch = request()->query('comment_search');
    $gameSetSearch = request()->query('game_set_search');
    $forumCommentSearch = request()->query('forum_comment_search');
    
    // Search using the terms (default or provided).
    $games = !empty($gameSearch)
        ? Game::search($gameSearch)->get()
        : collect();

    $users = !empty($userSearch)
        ? User::search($userSearch)->get()
        : collect();
    
    $comments = !empty($commentSearch) 
        ? Comment::search($commentSearch)->take(20)->get() 
        : collect();
        
    $gameSets = !empty($gameSetSearch) 
        ? GameSet::search($gameSetSearch)->get() 
        : collect();
        
    $forumComments = !empty($forumCommentSearch) 
        ? ForumTopicComment::search($forumCommentSearch)->take(20)->get() 
        : collect();
@endphp

<x-app-layout>
    <div class="flex flex-col gap-6">
        <form method="GET" action="" class="grid grid-cols-3 gap-4 mb-4">
            <div>
                <label for="game_search" class="block text-sm mb-1">Game Search</label>
                <input 
                    type="text" 
                    id="game_search"
                    name="game_search" 
                    value="{{ $gameSearch }}" 
                    placeholder="Search games"
                    class="rounded border px-2 py-1 w-full"
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
                    class="rounded border px-2 py-1 w-full"
                >
            </div>
            
            <div>
                <label for="comment_search" class="block text-sm mb-1">Comment Search</label>
                <input 
                    type="text" 
                    id="comment_search"
                    name="comment_search" 
                    value="{{ $commentSearch }}" 
                    placeholder="Search comments"
                    class="rounded border px-2 py-1 w-full"
                >
            </div>
            
            <div>
                <label for="game_set_search" class="block text-sm mb-1">Game Set Search</label>
                <input 
                    type="text" 
                    id="game_set_search"
                    name="game_set_search" 
                    value="{{ $gameSetSearch }}" 
                    placeholder="Search game sets"
                    class="rounded border px-2 py-1 w-full"
                >
            </div>
            
            <div>
                <label for="forum_comment_search" class="block text-sm mb-1">Forum Comment Search</label>
                <input 
                    type="text" 
                    id="forum_comment_search"
                    name="forum_comment_search" 
                    value="{{ $forumCommentSearch }}" 
                    placeholder="Search forum comments"
                    class="rounded border px-2 py-1 w-full"
                >
            </div>
            
            <div class="flex items-end">
                <button 
                    type="submit" 
                    class="btn"
                >
                    Search
                </button>
            </div>
        </form>
        
        <div class="space-y-8">
            <div>
                <h2 class="text-lg font-bold mb-2">Games ({{ $games->count() }})</h2>
                @if($games->isNotEmpty())
                    @dump($games->toArray())
                @else
                    <p class="text-gray-500">{{ $commentSearch ? 'No game results found' : 'Enter a search term to find games' }}</p>
                @endif
            </div>

            <div>
                <h2 class="text-lg font-bold mb-2">Users ({{ $users->count() }})</h2>
                @if($users->isNotEmpty())
                    @dump($users->toArray())
                @else
                    <p class="text-gray-500">{{ $commentSearch ? 'No user results found' : 'Enter a search term to find users' }}</p>
                @endif
            </div>
            
            <div>
                <h2 class="text-lg font-bold mb-2">Comments ({{ $comments->count() }})</h2>
                @if($comments->isNotEmpty())
                    @dump($comments->toArray())
                @else
                    <p class="text-gray-500">{{ $commentSearch ? 'No comment results found' : 'Enter a search term to find comments' }}</p>
                @endif
            </div>
            
            <div>
                <h2 class="text-lg font-bold mb-2">Game Sets ({{ $gameSets->count() }})</h2>
                @if($gameSets->isNotEmpty())
                    @dump($gameSets->toArray())
                @else
                    <p class="text-gray-500">{{ $gameSetSearch ? 'No game set results found' : 'Enter a search term to find game sets' }}</p>
                @endif
            </div>
            
            <div>
                <h2 class="text-lg font-bold mb-2">Forum Comments ({{ $forumComments->count() }})</h2>
                @if($forumComments->isNotEmpty())
                    @dump($forumComments->toArray())
                @else
                    <p class="text-gray-500">{{ $forumCommentSearch ? 'No forum comment results found' : 'Enter a search term to find forum comments' }}</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
