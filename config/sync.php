<?php

use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\Game;
use App\Models\GameAlternative;
use App\Models\GameHash;
use App\Models\GameSetGame;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\LeaderboardEntryLegacy;
use App\Models\MemoryNote;
use App\Models\Message;
use App\Models\News;
use App\Models\PlayerBadge;
use App\Models\PlayerSession;
use App\Models\Rating;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserRelation;
use App\Models\Vote;
use App\Support\Sync\SyncStrategy;

return [

    /*
     * media path is used to copy media assets from the same filesystem
     */
    'media_path' => env('LEGACY_MEDIA_PATH', storage_path('app/media')),

    'kinds' => [

        /*
         * Achievements           83k    incremental by DateModified
         */
        // TODO
        // 'achievements' => [
        //     'model' => Achievement::class,
        //     'strategy' => SyncStrategy::UPSERT,
        //     'reference_model' => Achievement::class,
        //     // TODO: make sure timestamp is updated on badge upload
        //     'reference_key' => 'Updated',
        //     'unique_key' => 'ID',
        //     'require' => [
        //         'games',
        //     ],
        //     'map' => [
        //         'AuthorID' => [
        //             'key' => 'user_id',
        //         ],
        //     ],
        // ],

        /*
         * Comment               200k    incremental by Submitted
         */
        // TODO
        // 'comments' => [
        //     'model' => Comment::class,
        //     'strategy' => SyncStrategy::UPSERT,
        //     'reference_model' => Comment::class,
        //     'reference_key' => 'Submitted',
        //     'unique_key' => 'ID',
        //     'require' => [
        //     ],
        //     'map' => [
        //         'Payload' => [
        //             'key' => 'body',
        //             'fixEncoding' => true,
        //         ],
        //     ],
        // ],

        /*
         * ForumCategories         0k    incremental by Updated
         */
        // TODO
        // 'forum_categories' => [
        //     'model' => ForumCategory::class,
        //     'strategy' => SyncStrategy::UPSERT,
        //     'reference_model' => ForumCategory::class,
        //     'reference_key' => 'Updated',
        //     'unique_key' => 'ID',
        //     'map' => [
        //         'Description' => [
        //             'key' => 'description',
        //             'fixEncoding' => true,
        //         ],
        //     ],
        // ],

        /*
         * Forums                  0k    incremental by Updated
         */
        // TODO
        // 'forums' => [
        //     'model' => Forum::class,
        //     'strategy' => SyncStrategy::UPSERT,
        //     'reference_model' => Forum::class,
        //     'reference_key' => 'Updated',
        //     'unique_key' => 'ID',
        //     'require' => [
        //         'forum_categories',
        //     ],
        //     'map' => [
        //         'Description' => [
        //             'key' => 'description',
        //             'fixEncoding' => true,
        //         ],
        //     ],
        // ],

        /*
         * ForumTopicComment            150k    incremental by DateModified
         */
        // 'forum_topics' => [
        //     'model' => ForumTopicComment::class,
        //     'strategy' => SyncStrategy::UPSERT,
        //     'reference_model' => ForumTopicComment::class,
        //     'reference_key' => 'Updated',
        //     'unique_key' => 'ID',
        //     'require' => [
        //         'forum_topics',
        //     ],
        //     'map' => [
        //         'Payload' => [
        //             'key' => 'body',
        //             'fixEncoding' => true,
        //         ],
        //     ],
        // ],

        /*
         * GameData               13k    incremental by Updated
         */
        // TODO
        // 'games' => [
        //     'model' => Game::class,
        //     'strategy' => SyncStrategy::UPSERT,
        //     'reference_model' => Game::class,
        //     'reference_key' => 'Updated',
        //     'unique_key' => 'ID',
        //     'require' => [
        //         'systems',
        //     ],
        //     'map' => [
        //         'Title' => [
        //             'key' => 'title',
        //             'fixEncoding' => true,
        //         ],
        //     ],
        // ],

        /*
         * GameAlternatives       33k    incremental by Created
         */
        // TODO
        // 'game_sets' => [
        //     'model' => GameSetGame::class,
        //     'strategy' => SyncStrategy::UPSERT,
        //     'reference_model' => GameAlternative::class,
        //     'reference_key' => 'Created',
        //     'unique_key' => 'id',
        //     'require' => [
        //         'games',
        //     ],
        //     'map' => [
        //     ],
        // ],

        /*
         * LeaderboardDef          5k    incremental by Updated
         */
        // TODO
        // 'leaderboards' => [
        //     'model' => Leaderboard::class,
        //     'strategy' => SyncStrategy::UPSERT,
        //     'reference_model' => Leaderboard::class,
        //     'reference_key' => 'Updated',
        //     'unique_key' => 'ID',
        //     'require' => [
        //     ],
        //     'map' => [
        //     ],
        // ],

        /*
         * LeaderboardEntry      1500k    incremental by DateSubmitted
         */
        'leaderboard_entries' => [
            'model' => LeaderboardEntry::class,
            'strategy' => SyncStrategy::UPSERT,
            'reference_model' => LeaderboardEntryLegacy::class,
            'reference_key' => LeaderboardEntryLegacy::UPDATED_AT,
            'unique_key' => ['leaderboard_id', 'user_id'],
            'require' => [
            ],
            'map' => [
                'LeaderboardID' => [
                    'key' => 'leaderboard_id',
                ],
                'UserID' => [
                    'key' => 'user_id',
                ],
                'Score' => [
                    'key' => 'score',
                    'type' => 'integer',
                ],
                'Created' => [
                    'key' => 'created_at',
                ],
                'DateSubmitted' => [
                    'key' => 'updated_at',
                ],
            ],
        ],

        /*
         * CodeNotes             150k    incremental by Updated
         */
        // TODO
        // 'memory_notes' => [
        //     'model' => MemoryNote::class,
        //     'strategy' => SyncStrategy::UPSERT,
        //     'reference_model' => MemoryNote::class,
        //     'reference_key' => 'Created',
        //     'unique_key' => ['game_hash_set_id', 'address', 'user_id'],
        //     'require' => [
        //         'games',
        //         'game_hashes',
        //     ],
        //     'map' => [
        //         'Note' => [
        //             'key' => 'body',
        //             'fixEncoding' => true,
        //         ],
        //     ],
        // ],

        /*
         * Messages               57k    incremental by TimeSent
         */
        // TODO
        // 'messages' => [
        //     'model' => Message::class,
        //     'strategy' => SyncStrategy::UPSERT,
        //     'reference_model' => Message::class,
        //     'reference_key' => 'TimeSent',
        //     'unique_key' => 'id',
        //     'require' => [
        //     ],
        //     'map' => [
        //     ],
        // ],

        /*
         * News                    0k    incremental by Updated
         */
        // TODO
        // 'news' => [
        //     'model' => News::class,
        //     'strategy' => SyncStrategy::UPSERT,
        //     'reference_model' => News::class,
        //     'reference_key' => 'Updated',
        //     'unique_key' => 'id',
        //     'require' => [
        //     ],
        //     'map' => [
        //         'AuthorID' => [
        //             'key' => 'user_id',
        //         ],
        //         'Payload' => [
        //             'key' => 'lead',
        //             'fixEncoding' => true,
        //         ],
        //     ],
        // ],

        /*
         * SiteAwards            106k    incremental by AwardDate
         */
        // TODO
        // 'player_badges' => [
        //     'model' => PlayerBadge::class,
        //     'strategy' => SyncStrategy::UPSERT,
        //     'reference_model' => PlayerBadge::class,
        //     'reference_key' => 'AwardDate',
        //     'require' => [
        //     ],
        //     'map' => [
        //         'UserID' => [
        //             'key' => 'user_id',
        //         ],
        //     ],
        // ],

        /*
         * Rating                 41k    incremental by Updated
         */
        // TODO
        // 'ratings' => [
        //     'model' => Rating::class,
        //     'strategy' => SyncStrategy::UPSERT,
        //     'reference_model' => Rating::class,
        //     'reference_key' => 'Updated',
        //     'unique_key' => 'id',
        //     'require' => [
        //     ],
        //     'map' => [
        //     ],
        // ],

        /*
         * GameHashLibrary        22k    incremental by Created
         */
        // TODO
        // 'game_hashes' => [
        //     'model' => GameHash::class,
        //     'strategy' => SyncStrategy::UPSERT,
        //     'reference_model' => GameHash::class,
        //     'reference_key' => 'Created',
        //     'unique_key' => ['system_id', 'hash'],
        //     'require' => [
        //         'games',
        //     ],
        //     'map' => [
        //         'MD5' => [
        //             'key' => 'hash',
        //         ],
        //     ],
        // ],

        /*
         * Console                 0k    incremental by Updated
         */
        // TODO
        // 'systems' => [
        //     'model' => System::class,
        //     'strategy' => SyncStrategy::UPSERT,
        //     'reference_model' => System::class,
        //     'reference_key' => 'Updated',
        //     'unique_key' => 'ID',
        //     'map' => [
        //     ],
        // ],

        /*
         * Ticket                 18k    incremental by Updated
         */
        // TODO
        // 'tickets' => [
        //     'model' => Ticket::class,
        //     'strategy' => SyncStrategy::UPSERT,
        //     'reference_model' => Ticket::class,
        //     'reference_key' => 'Updated',
        //     'unique_key' => 'ID',
        //     'require' => [
        //         'achievements',
        //     ],
        //     'map' => [
        //     ],
        // ],

        /*
         * UserAccounts          106k    incremental by Updated
         */
        // TODO
        // 'users' => [
        //     'model' => User::class,
        //     'strategy' => SyncStrategy::UPSERT,
        //     'reference_model' => User::class,
        //     // TODO: make sure timestamp is updated on avatar upload
        //     'reference_key' => 'Updated',
        //     'unique_key' => 'ID',
        //     'map' => [
        //         'Motto' => [
        //             'key' => 'motto',
        //             'fixEncoding' => true,
        //         ],
        //     ],
        // ],

        // TODO
        // 'player_rich_presence' => [
        //     'model' => PlayerSession::class,
        //     'strategy' => SyncStrategy::UPSERT,
        //     'reference_table' => 'UserAccounts',
        //     'reference_key' => 'RichPresenceMsgDate',
        //     'unique_key' => 'ID',
        //     'require' => [
        //         'user_achievements', /* really just the player_sessions */
        //     ],
        //     'map' => [
        //        /* no map. everything is handled in preProcessEntity */
        //         // 'RichPresenceMsg' => [
        //         //     'key' => 'rich_presence',
        //         // ],
        //         // 'RichPresenceMsgDate' => [
        //         //     'key' => 'rich_presence_updated_at',
        //         // ],
        //     ],
        // ],

        /*
         * SetRequest           50k    incremental by Updated
         */
        // TODO
        // 'user_game_list_entries' => [
        //     'model' => UserGameListEntry::class,
        //     'strategy' => SyncStrategy::UPSERT,
        //     'reference_model' => UserGameListEntry::class,
        //     'reference_key' => 'Updated',
        //     'require' => [
        //         'games',
        //     ],
        //     'map' => [
        //         'UserID' => [
        //             'key' => 'user_id',
        //         ],
        //     ],
        // ],

        /*
         * Friends                43k    incremental by Updated
         */
        // TODO
        // 'user_relations' => [
        //     'model' => UserRelation::class,
        //     'strategy' => SyncStrategy::UPSERT,
        //     'reference_table' => 'Friends',
        //     'reference_key' => 'Updated',
        //     'require' => [
        //     ],
        //     'map' => [
        //     ],
        // ],

        /*
         * Votes                   0k    incremental by Updated
         */
        // TODO
        // 'votes' => [
        //     'model' => Vote::class,
        //     'strategy' => SyncStrategy::UPSERT,
        //     'reference_table' => 'Votes',
        //     'reference_key' => 'Updated',
        //     'require' => [
        //         'achievements',
        //     ],
        //     'map' => [
        //     ],
        // ],
    ],
];
