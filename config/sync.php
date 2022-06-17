<?php

use App\Community\Models\AchievementSetRequest;
use App\Community\Models\Comment;
use App\Community\Models\Forum;
use App\Community\Models\ForumCategory;
use App\Community\Models\ForumTopic;
use App\Community\Models\Message;
use App\Community\Models\News;
use App\Community\Models\Rating;
use App\Community\Models\Ticket;
use App\Community\Models\UserRelation;
use App\Community\Models\Vote;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\GameHash;
use App\Platform\Models\GameRelation;
use App\Platform\Models\Leaderboard;
use App\Platform\Models\LeaderboardEntry;
use App\Platform\Models\MemoryNote;
use App\Platform\Models\PlayerAchievement;
use App\Platform\Models\PlayerBadge;
use App\Platform\Models\PlayerSession;
use App\Platform\Models\System;
use App\Site\Models\User;
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
        'achievements' => [
            'model' => Achievement::class,
            'strategy' => SyncStrategy::UPSERT,
            'reference_model' => \App\Legacy\Models\Achievement::class,
            // TODO: make sure timestamp is updated on badge upload
            'reference_key' => 'Updated',
            'unique_key' => 'id',
            'require' => [
                'games',
                'users',
            ],
            'map' => [
                'ID' => [
                    'key' => 'id',
                ],
                'GameID' => [
                    'key' => 'game_id',
                ],
                'Description' => [
                    'key' => 'description',
                ],
                // 'MemAddr' => [
                //     'key' => 'trigger',
                // ],
                // 'Progress' => [
                //     'key' => 'progress',
                // ],
                // 'ProgressMax' => [
                //     'key' => 'progress_max',
                // ],
                // 'ProgressFormat' => [
                //     'key' => 'progress_format',
                // ],
                'Points' => [
                    'key' => 'points',
                ],
                // 'Flags' => [
                //     'key' => 'status_flag',
                // ],
                // 'Author' => [
                //     'key' => 'user_name',
                // ],
                'AuthorID' => [
                    'key' => 'user_id',
                ],
                'DateCreated' => [
                    'key' => 'created_at',
                ],
                'DateModified' => [
                    'key' => 'updated_at',
                ],
                // 'VotesPos' => [
                //     'key' => 'votes_positive',
                // ],
                // 'VotesNeg' => [
                //     'key' => 'votes_negative',
                // ],
                // 'BadgeName' => [
                //     'key' => 'badge_name',
                // ],
                // 'DisplayOrder' => [
                //     'key' => 'order_column',
                // ],
                // 'AssocVideo' => [
                //     'key' => 'video',
                // ],
                'Title' => [
                    'key' => 'title',
                ],
                // 'TrueRatio' => [
                //     'key' => 'points_weighted',
                // ],
            ],
        ],

        'achievement_set_requests' => [
            'model' => AchievementSetRequest::class,
            'strategy' => SyncStrategy::UPSERT,
            'reference_model' => \App\Legacy\Models\AchievementSetRequest::class,
            'reference_key' => 'Updated',
            'require' => [
                'games',
                'users',
            ],
            'map' => [
                'UserID' => [
                    'key' => 'user_id',
                ],
                'GameID' => [
                    'key' => 'game_id',
                ],
                'Updated' => [
                    'key' => 'updated_at',
                    'type' => 'timestamp',
                ],
            ],
        ],

        /*
         * Comment               200k    incremental by Submitted
         */
        'comments' => [
            'model' => Comment::class,
            'strategy' => SyncStrategy::UPSERT,
            'reference_model' => \App\Legacy\Models\Comment::class,
            'reference_key' => 'Submitted',
            'unique_key' => 'origin_id',
            'require' => [
                'users',
            ],
            'map' => [
                'Payload' => [
                    'key' => 'body',
                    'fixEncoding' => true,
                ],
                'ExistingAuthorID' => [
                    'key' => 'user_id',
                ],
                'DateCreated' => [
                    'key' => 'created_at',
                    'type' => 'timestamp',
                ],
                'DateUpdated' => [
                    'key' => 'updated_at',
                    'type' => 'timestamp',
                ],
            ],
        ],

        // /**
        //  *
        //  */
        // 'deleted_models' => [
        //
        // ],

        /*
         * ForumCategories         0k    incremental by Updated
         */
        'forum_categories' => [
            'model' => ForumCategory::class,
            'strategy' => SyncStrategy::UPSERT,
            'reference_model' => \App\Legacy\Models\ForumCategory::class,
            'reference_key' => 'Updated',
            'unique_key' => 'id',
            'map' => [
                'ID' => [
                    'key' => 'id',
                ],
                'Name' => [
                    'key' => 'title',
                ],
                'Description' => [
                    'key' => 'description',
                    'fixEncoding' => true,
                ],
                'DisplayOrder' => [
                    'key' => 'order_column',
                ],
            ],
        ],

        /*
         * Forums                  0k    incremental by Updated
         */
        'forums' => [
            'model' => Forum::class,
            'strategy' => SyncStrategy::UPSERT,
            'reference_model' => \App\Legacy\Models\Forum::class,
            'reference_key' => 'Updated',
            'unique_key' => 'id',
            'require' => [
                'forum_categories',
            ],
            'map' => [
                'ID' => [
                    'key' => 'id',
                ],
                'Title' => [
                    'key' => 'title',
                ],
                'Description' => [
                    'key' => 'description',
                    'fixEncoding' => true,
                ],
                'DisplayOrder' => [
                    'key' => 'order_column',
                ],
                'CategoryID' => [
                    'key' => 'forum_category_id',
                ],
            ],
        ],

        /*
         * ForumTopics            60k    incremental by derived dates
         */
        'forum_topics' => [
            'model' => ForumTopic::class,
            'strategy' => SyncStrategy::UPSERT,
            'reference_model' => \App\Legacy\Models\ForumTopic::class,
            'reference_key' => 'LastForumCommentDateUpdated',
            'reference_column' => 'LastForumCommentDateUpdated',
            'unique_key' => 'id',
            'require' => [
                'users',
                'forums',
                'games',
            ],
            'map' => [
                'ID' => [
                    'key' => 'id',
                ],
                'ForumID' => [
                    'key' => 'forum_id',
                ],
                'Title' => [
                    'key' => 'title',
                    'fixEncoding' => true,
                ],
                'ExistingAuthorID' => [
                    'key' => 'user_id',
                ],
                'RequiredPermissions' => [
                    'key' => 'permissions',
                ],
                'FirstForumCommentPayload' => [
                    'key' => 'body',
                    'fixEncoding' => true,
                ],
                'FirstForumCommentDateCreated' => [
                    'key' => 'created_at',
                ],
                'FirstForumCommentDateUpdated' => [
                    'key' => 'updated_at',
                ],
            ],
        ],

        /*
         * GameData               13k    incremental by Updated
         */
        'games' => [
            'model' => Game::class,
            'strategy' => SyncStrategy::UPSERT,
            'reference_model' => \App\Legacy\Models\Game::class,
            'reference_key' => 'Updated',
            'unique_key' => 'id',
            'require' => [
                'users',
                'systems',
            ],
            'map' => [
                'ID' => [
                    'key' => 'id',
                ],
                'ConsoleID' => [
                    'key' => 'system_id',
                ],
                // 'ForumTopicID' => [
                //     'key' => 'forum_topic_id',
                // ],
                'Title' => [
                    'key' => 'title',
                    'fixEncoding' => true,
                ],
                // 'Flags' => [
                //     'key' => 'status_flag',
                // ],
                // 'ImageIcon' => [
                //     'key' => 'image_icon',
                // ],
                // 'ImageTitle' => [
                //     'key' => 'image_title',
                // ],
                // 'ImageIngame' => [
                //     'key' => 'image_in_game',
                // ],
                // 'ImageBoxArt' => [
                //     'key' => 'image_box_art',
                // ],
                // 'Publisher' => [
                //     'key' => 'publisher',
                // ],
                // 'Developer' => [
                //     'key' => 'developer',
                // ],
                // 'Genre' => [
                //     'key' => 'genre',
                // ],
                'Released' => [
                    'key' => 'release',
                ],
                // 'IsFinal' => [
                //     'key' => 'final',
                // ],
                // 'RichPresencePatch' => [
                //     'key' => 'rich_presence_patch',
                // ],
                // 'TotalTruePoints' => [
                //     'key' => 'points_weighted',
                // ],
                'Created' => [
                    'key' => 'created_at',
                ],
                'Updated' => [
                    'key' => 'updated_at',
                ],
            ],
        ],

        /*
         * GameAlternatives       33k    incremental by Created
         */
        'game_relations' => [
            'model' => GameRelation::class,
            'strategy' => SyncStrategy::UPSERT,
            'reference_model' => \App\Legacy\Models\GameRelation::class,
            'reference_key' => 'Created',
            'unique_key' => 'id',
            'require' => [
                'games',
            ],
            'map' => [
            ],
        ],

        /*
         * LeaderboardDef          5k    incremental by Updated
         */
        'leaderboards' => [
            'model' => Leaderboard::class,
            'strategy' => SyncStrategy::UPSERT,
            'reference_model' => \App\Legacy\Models\Leaderboard::class,
            'reference_key' => 'Updated',
            'unique_key' => 'id',
            'require' => [
                'games',
            ],
            'map' => [
            ],
        ],

        /*
         * LeaderboardEntry      270k    incremental by DateSubmitted
         */
        'leaderboard_entries' => [
            'model' => LeaderboardEntry::class,
            'strategy' => SyncStrategy::UPSERT,
            'reference_model' => \App\Legacy\Models\LeaderboardEntry::class,
            'reference_key' => 'DateSubmitted',
            'unique_key' => 'id',
            'require' => [
                'leaderboards',
            ],
            'map' => [
            ],
        ],

        /*
         * CodeNotes             150k    incremental by Updated
         */
        'memory_notes' => [
            'model' => MemoryNote::class,
            'strategy' => SyncStrategy::UPSERT,
            'reference_model' => \App\Legacy\Models\MemoryNote::class,
            'reference_key' => 'Created',
            'unique_key' => ['game_hash_set_id', 'address', 'user_id'],
            'require' => [
                'games',
                'game_hashes',
            ],
            'map' => [
                // 'GameID' => [
                //     'key' => 'game_id',
                // ],
                'Address' => [
                    'key' => 'address',
                ],
                'AuthorID' => [
                    'key' => 'user_id',
                ],
                'Note' => [
                    'key' => 'body',
                    'fixEncoding' => true,
                ],
                'Created' => [
                    'key' => 'created_at',
                ],
                'Updated' => [
                    'key' => 'updated_at',
                ],
            ],
        ],

        /*
         * Messages               57k    incremental by TimeSent
         */
        'messages' => [
            'model' => Message::class,
            'strategy' => SyncStrategy::UPSERT,
            'reference_model' => \App\Legacy\Models\Message::class,
            'reference_key' => 'TimeSent',
            'unique_key' => 'id',
            'require' => [
                'users',
            ],
            'map' => [
            ],
        ],

        /*
         * News                    0k    incremental by Updated
         */
        'news' => [
            'model' => News::class,
            'strategy' => SyncStrategy::UPSERT,
            'reference_model' => \App\Legacy\Models\News::class,
            'reference_key' => 'Updated',
            'unique_key' => 'id',
            'require' => [
                'users',
            ],
            'map' => [
                'ID' => [
                    'key' => 'id',
                ],
                'Timestamp' => [
                    'key' => 'created_at',
                ],
                'Title' => [
                    'key' => 'title',
                ],
                'Payload' => [
                    'key' => 'lead',
                    'fixEncoding' => true,
                ],
                'AuthorID' => [
                    'key' => 'user_id',
                ],
                // 'Image' => [
                //     'key' => 'image',
                // ],
            ],
        ],

        /*
         * Awarded             12338k    incremental by Date
         */
        'player_achievements' => [
            'model' => PlayerAchievement::class,
            'strategy' => SyncStrategy::UPSERT,
            'reference_model' => \App\Legacy\Models\PlayerAchievement::class,
            'reference_key' => 'Date',
            'unique_key' => 'id',
            'require' => [
                'users',
                'achievements',
            ],
            'map' => [
                /* no map. everything is handled in preProcessEntity */
            ],
        ],

        /*
         * SiteAwards            106k    incremental by AwardDate
         */
        'player_badges' => [
            'model' => PlayerBadge::class,
            'strategy' => SyncStrategy::UPSERT,
            'reference_model' => \App\Legacy\Models\PlayerBadge::class,
            'reference_key' => 'AwardDate',
            'require' => [
                'users',
            ],
            'map' => [
                'UserID' => [
                    'key' => 'user_id',
                ],
                'AwardDate' => [
                    'key' => 'created_at',
                    'type' => 'timestamp',
                ],
            ],
        ],

        /*
         * Rating                 41k    incremental by Updated
         */
        'ratings' => [
            'model' => Rating::class,
            'strategy' => SyncStrategy::UPSERT,
            'reference_model' => \App\Legacy\Models\Rating::class,
            'reference_key' => 'Updated',
            'unique_key' => 'id',
            'require' => [
                'games',
            ],
            'map' => [
            ],
        ],

        /*
         * GameHashLibrary        22k    incremental by Created
         */
        'game_hashes' => [
            'model' => GameHash::class,
            'strategy' => SyncStrategy::UPSERT,
            'reference_model' => \App\Legacy\Models\GameHash::class,
            'reference_key' => 'Created',
            'unique_key' => ['system_id', 'hash'],
            'require' => [
                'games',
            ],
            'map' => [
                'MD5' => [
                    'key' => 'hash',
                ],
            ],
        ],

        /*
         * Console                 0k    incremental by Updated
         */
        'systems' => [
            'model' => System::class,
            'strategy' => SyncStrategy::UPSERT,
            'reference_model' => \App\Legacy\Models\System::class,
            'reference_key' => 'Updated',
            'unique_key' => 'id',
            'map' => [
                'ID' => [
                    'key' => 'id',
                ],
                'Name' => [
                    'key' => 'name',
                ],
            ],
        ],

        /*
         * Ticket                 18k    incremental by Updated
         */
        'tickets' => [
            'model' => Ticket::class,
            'strategy' => SyncStrategy::UPSERT,
            'reference_model' => \App\Legacy\Models\Ticket::class,
            'reference_key' => 'Updated',
            'unique_key' => 'id',
            'require' => [
                'achievements',
            ],
            'map' => [
            ],
        ],

        /*
         * UserAccounts          106k    incremental by Updated
         */
        'users' => [
            'model' => User::class,
            'strategy' => SyncStrategy::UPSERT,
            'reference_model' => \App\Legacy\Models\User::class,
            // TODO: make sure timestamp is updated on avatar upload
            'reference_key' => 'Updated',
            'unique_key' => 'id',
            'map' => [
                'ID' => [
                    'key' => 'id',
                ],
                'User' => [
                    'key' => 'username',
                ],
                'EmailAddress' => [
                    'key' => 'email',
                ],
                'Password' => [
                    'key' => 'password',
                ],
                // 'SaltedPass' => [
                //     'key' => 'password_legacy',
                // ],
                // 'fbUser' => [
                //     'key' => 'facebook_user_id',
                // ],
                // 'fbPrefs' => [
                //     'key' => 'facebook_user_preferences',
                // ],
                'appToken' => [
                    'key' => 'connect_token',
                ],
                'appTokenExpiry' => [
                    'key' => 'connect_token_expires_at',
                    'type' => 'timestamp',
                ],
                // 'cookie' => [
                //     'key' => 'remember_token',
                // ],
                // 'websitePrefs' => [
                //     'key' => 'preferences_legacy',
                // ],
                // 'Permissions' => [
                //     'key' => 'role_id',
                // ],
                'LastLogin' => [
                    'key' => 'last_activity_at',
                    'type' => 'timestamp',
                ],
                // 'LastActivityID' => [
                //     'key' => 'last_activity_id',
                // ],
                'Motto' => [
                    'key' => 'motto',
                    'fixEncoding' => true,
                ],
                // 'ContribCount' => [
                //     'key' => 'achievements_unlocked_yield',
                // ],
                // 'ContribYield' => [
                //     'key' => 'achievements_points_yield',
                // ],
                'APIKey' => [
                    'key' => 'api_token',
                ],
                'APIUses' => [
                    'key' => 'api_calls',
                ],
                // 'LastGameID' => [
                //     'key' => 'last_game_id',
                // ],
                // 'ManuallyVerified' => [
                //     'key' => 'forum_verified_at',
                // ],
                // 'UnreadMessageCount' => [
                //     'key' => 'unread_messages_count',
                // ],
                'RAPoints' => [
                    'key' => 'points_total',
                ],
                'TrueRAPoints' => [
                    'key' => 'points_weighted',
                ],
                // 'UserWallActive' => [
                //     'key' => 'wall_active',
                // ],
                // 'Unranked' => [
                //     'key' => 'unranked',
                // ],
                'Created' => [
                    'key' => 'created_at',
                    'type' => 'timestamp',
                ],
                'Updated' => [
                    'key' => 'updated_at',
                    'type' => 'timestamp',
                ],
                'DeleteRequested' => [
                    'key' => 'delete_requested_at',
                    'type' => 'timestamp',
                ],
                'Deleted' => [
                    'key' => 'deleted_at',
                    'type' => 'timestamp',
                ],
            ],
        ],

        'user_rich_presence' => [
            'model' => PlayerSession::class,
            'strategy' => SyncStrategy::UPSERT,
            'reference_table' => 'UserAccounts',
            'reference_key' => 'RichPresenceMsgDate',
            'unique_key' => 'id',
            'require' => [
                'user_achievements', /* really just the player_sessions */
            ],
            'map' => [
               /* no map. everything is handled in preProcessEntity */
                // 'RichPresenceMsg' => [
                //     'key' => 'rich_presence',
                // ],
                // 'RichPresenceMsgDate' => [
                //     'key' => 'rich_presence_updated_at',
                // ],
            ],
        ],

        /*
         * Friends                43k    incremental by Updated
         */
        'user_relations' => [
            'model' => UserRelation::class,
            'strategy' => SyncStrategy::UPSERT,
            'reference_table' => 'Friends',
            'reference_key' => 'Updated',
            'require' => [
                'users',
            ],
            'map' => [
            ],
        ],

        /*
         * Votes                   0k    incremental by Updated
         */
        'votes' => [
            'model' => Vote::class,
            'strategy' => SyncStrategy::UPSERT,
            'reference_table' => 'Votes',
            'reference_key' => 'Updated',
            'require' => [
                'achievements',
            ],
            'map' => [
            ],
        ],
    ],
];
