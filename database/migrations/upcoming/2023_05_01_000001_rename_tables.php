<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        // TODO update metabase

        if (DB::connection()->getDriverName() === 'sqlite') {
            // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
            Schema::rename('Achievements', 'achievementsTMP');
            Schema::rename('achievementsTMP', 'achievements');
        } else {
            Schema::rename('Achievements', 'achievements');
        }
        Schema::rename('CodeNotes', 'memory_notes');
        Schema::rename('Comment', 'comments');
        Schema::rename('Console', 'systems');
        Schema::rename('Forum', 'forums');
        Schema::rename('ForumCategory', 'forum_categories');
        Schema::rename('ForumTopic', 'forum_topics');
        Schema::rename('ForumTopicComment', 'forum_topic_comments');
        Schema::rename('Friends', 'user_relations');
        Schema::rename('GameData', 'games');
        Schema::rename('GameHashLibrary', 'game_hashes');
        Schema::rename('LeaderboardDef', 'leaderboards');
        Schema::rename('LeaderboardEntry', 'leaderboard_entries');
        if (DB::connection()->getDriverName() === 'sqlite') {
            // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
            Schema::rename('Messages', 'messagesTMP');
            Schema::rename('messagesTMP', 'messages');
        } else {
            Schema::rename('Messages', 'messages');
        }
        if (DB::connection()->getDriverName() === 'sqlite') {
            // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
            Schema::rename('News', 'newsTMP');
            Schema::rename('newsTMP', 'news');
        } else {
            Schema::rename('News', 'news');
        }
        Schema::rename('Rating', 'ratings');
        Schema::rename('SetClaim', 'achievement_set_claims');
        Schema::rename('SetRequest', 'user_game_list_entries');
        Schema::rename('Ticket', 'tickets');
        Schema::rename('UserAccounts', 'users');
        if (DB::connection()->getDriverName() === 'sqlite') {
            // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
            Schema::rename('Votes', 'votesTMP');
            Schema::rename('votesTMP', 'votes');
        } else {
            Schema::rename('Votes', 'votes');
        }
    }

    public function down(): void
    {
        Schema::rename('achievements', 'Achievements');
        Schema::rename('comments', 'Comment');
        Schema::rename('forums', 'Forum');
        Schema::rename('forum_categories', 'ForumCategory');
        Schema::rename('forum_topics', 'ForumTopic');
        Schema::rename('forum_topic_comments', 'ForumTopicComment');
        Schema::rename('games', 'GameData');
        Schema::rename('game_hashes', 'GameHashLibrary');
        Schema::rename('leaderboards', 'LeaderboardDef');
        Schema::rename('leaderboard_entries', 'LeaderboardEntry');
        Schema::rename('memory_notes', 'CodeNotes');
        Schema::rename('messages', 'Messages');
        Schema::rename('news', 'News');
        Schema::rename('ratings', 'Rating');
        Schema::rename('systems', 'Console');
        Schema::rename('tickets', 'Ticket');
        Schema::rename('user_game_list_entries', 'SetRequest');
        Schema::rename('achievement_set_claims', 'SetClaim');
        Schema::rename('user_relations', 'Friends');
        Schema::rename('users', 'UserAccounts');
        Schema::rename('votes', 'Votes');
    }
};
