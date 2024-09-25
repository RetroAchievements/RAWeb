<?php

return [
    /*
     * fallbacks
     */
    'resource' => [
        'title' => __('entry') . '|' . __('entries'),
    ],
    'title' => ':Resource',
    'empty' => __('No :resource'),

    /*
     * CRUD actions
     */
    'create' => [
        'success' => __(':Resource created successfully'),
        'error' => __(':Resource could not be created'),
    ],
    'update' => [
        'success' => __(':Resource updated successfully'),
        'error' => __(':Resource could not be updated'),
    ],
    'update-to' => [
        'success' => __(':Resource updated successfully to :value'),
        'error' => __(':Resource could not be changed to :value'),
    ],
    'delete' => [
        'success' => __(':Resource deleted successfully'),
        'error' => __(':Resource could not be deleted'),
    ],
    'restore' => [
        'success' => __(':Resource restored successfully'),
        'error' => __(':Resource could not be restored'),
    ],
    'ban' => [
        'success' => __(':Resource banned successfully'),
        'error' => __(':Resource could not be banned'),
    ],
    'unban' => [
        'success' => __(':Resource unbanned successfully'),
        'error' => __(':Resource could not be unbanned'),
    ],
    'mute' => [
        'success' => __(':Resource muted successfully'),
        'error' => __(':Resource could not be muted'),
    ],
    'unmute' => [
        'success' => __(':Resource unmuted successfully'),
        'error' => __(':Resource could not be unmuted'),
    ],
    'rank' => [
        'success' => __(':Resource ranked successfully'),
        'error' => __(':Resource could not be ranked'),
    ],
    'unrank' => [
        'success' => __(':Resource unranked successfully'),
        'error' => __(':Resource could not be unranked'),
    ],
    'drop' => [
        'success' => __(':Resource dropped successfully'),
        'error' => __(':Resource could not be dropped'),
    ],

    /*
     * resources below
     */
    'account' => [
        'title' => __('account') . '|' . __('accounts'),
    ],
    'author' => [
        'title' => __('author') . '|' . __('authors'),
    ],
    'application' => [
        'title' => __('application') . '|' . __('applications'),
    ],
    'achievement' => [
        'title' => __('achievement') . '|' . __('achievements'),
        'comment' => [
            'title' => __('comment') . '|' . __('comments'),
        ],
    ],
    'avatar' => [
        'title' => __('avatar') . '|' . __('avatars'),
    ],
    'award' => [
        'title' => __('award') . '|' . __('awards'),
    ],
    'badge' => [
        'title' => __('badge') . '|' . __('badges'),
    ],
    'claim' => [
        'title' => __('claim') . '|' . __('claims'),
    ],
    'comment' => [
        'title' => __('comment') . '|' . __('comments'),
    ],
    'emulator' => [
        'title' => __('emulator') . '|' . __('emulators'),
        'image' => [
            'title' => __('image') . '|' . __('images'),
        ],
        'release' => [
            'title' => __('release') . '|' . __('releases'),
        ],
    ],
    'forum' => [
        'title' => __('forum') . '|' . __('forums'),
        'topic' => [
            'title' => __('topic') . '|' . __('topics'),
        ],
    ],
    'forum-category' => [
        'title' => __('forum category') . '|' . __('forum categories'),
    ],
    'forum-topic' => [
        'title' => __('topic') . '|' . __('topics'),
        'comment' => [
            'title' => __('comment') . '|' . __('comments'),
        ],
    ],
    'friend' => [
        'title' => __('friend') . '|' . __('friends'),
    ],
    'game' => [
        'title' => __('game') . '|' . __('games'),
        'comment' => [
            'title' => __('comment') . '|' . __('comments'),
        ],
        'player' => [
            'title' => __('player') . '|' . __('players'),
        ],
    ],
    'integration' => [
        'title' => __('integration') . '|' . __('integrations'),
        'release' => [
            'title' => __('release') . '|' . __('releases'),
        ],
    ],
    'leaderboard' => [
        'title' => __('leaderboard') . '|' . __('leaderboards'),
    ],
    'library' => [
        'title' => __('library') . '|' . __('libraries'),
    ],
    'message' => [
        'title' => __('message') . '|' . __('messages'),
    ],
    'news' => [
        'title' => __('news') . '|' . __('news'),
        'image' => [
            'title' => __('news image') . '|' . __('news images'),
        ],
        'comment' => [
            'title' => __('comment') . '|' . __('comments'),
        ],
    ],
    'notification' => [
        'title' => __('notification') . '|' . __('notifications'),
    ],
    'point' => [
        'title' => __('point') . '|' . __('points'),
    ],
    'player' => [
        'title' => __('player') . '|' . __('players'),
    ],
    'player-session' => [
        'title' => __('player session') . '|' . __('player sessions'),
    ],
    'profile' => [
        'title' => __('profile') . '|' . __('profiles'),
    ],
    'release' => [
        'title' => __('release') . '|' . __('releases'),
    ],
    'reply' => [
        'title' => __('reply') . '|' . __('replies'),
    ],
    'role' => [
        'title' => __('role') . '|' . __('roles'),
    ],
    'game-hash' => [
        'title' => __('hash') . '|' . __('hashes'),
    ],
    'game-hash-set' => [
        'title' => __('set') . '|' . __('sets'),
    ],
    'ranking' => [
        'title' => __('ranking') . '|' . __('rankings'),
    ],
    'setting' => [
        'title' => __('setting') . '|' . __('settings'),
        'profile' => [
            'title' => __('profile setting') . '|' . __('profile settings'),
        ],
    ],
    'stream' => [
        'title' => __('stream') . '|' . __('streams'),
    ],
    'system' => [
        'title' => __('system') . '|' . __('systems'),
    ],
    'ticket' => [
        'title' => __('ticket') . '|' . __('tickets'),
    ],
    'user' => [
        'title' => __('user') . '|' . __('users'),
        'avatar' => [
            'title' => __('avatar') . '|' . __('avatars'),
        ],
        'comment' => [
            'title' => __('comment') . '|' . __('comments'),
        ],
        'post' => [
            'title' => __('post') . "|" . __('posts'),
        ],
        'motto' => [
            'title' => __('motto') . '|' . __('mottos'),
        ],
        'timezone' => [
            'title' => __('timezone') . '|' . __('timezones'),
        ],
    ],
    'vote' => [
        'title' => __('vote') . '|' . __('votes'),
        'star' => [
            'title' => __('star') . '|' . __('stars'),
        ],
    ],
];
