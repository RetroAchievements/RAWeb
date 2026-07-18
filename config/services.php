<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'bluesky' => [
        'channel' => env('BLUESKY_CHANNEL'),
    ],

    'cloudflare' => [
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
        'zone_id' => env('CLOUDFLARE_ZONE_ID'),
    ],

    'discord' => [
        'invite_id' => env('DISCORD_INVITE_ID'),
        'guild_id' => env('DISCORD_GUILD_ID'),
        'rabot_token' => env('DISCORD_RABOT_TOKEN'),
        'muted' => env('DISCORD_ROLE_MUTED'),
        'webhook' => [
            // public
            'achievements' => env('DISCORD_WEBHOOK_ACHIEVEMENTS'),
            'claims' => env('DISCORD_WEBHOOK_CLAIMS'),
            'name-changes' => env('DISCORD_WEBHOOK_NAME_CHANGES'), // available to many privileged roles
            'news' => env('DISCORD_WEBHOOK_NEWS'),
            'users' => env('DISCORD_WEBHOOK_USERS'),
            // moderation
            'forums' => env('DISCORD_WEBHOOK_MOD_FORUMS'),
            'sentry' => env('DISCORD_WEBHOOK_MOD_SENTRY'),
        ],
        'inbox_webhook' => [
            'CodeReviewTeam' => [
                'url' => env('DISCORD_WEBHOOK_CODEREVIEWERS'),
                'is_forum' => true,
            ],
            'DevCompliance' => [
                'url' => env('DISCORD_WEBHOOK_DEVCOMPLIANCE'),
                'is_forum' => true,
                'mention_role' => env('DISCORD_ROLE_DEVCOMPLIANCE'),
                'unwelcome_concept_url' => env('DISCORD_WEBHOOK_DEVCOMPLIANCE_UNWELCOME_CONCEPT'),
            ],
            'DevQuest' => [
                'url' => env('DISCORD_WEBHOOK_DEVQUEST'),
            ],
            'QATeam' => [
                'url' => env('DISCORD_WEBHOOK_QATEAM'),
                'is_forum' => true,
                'mention_role' => env('DISCORD_ROLE_QATEAM'),
                'achievement_issues_url' => env('DISCORD_WEBHOOK_QATEAM_ACHIEVEMENT_ISSUES'),
                'incorrect_type_url' => env('DISCORD_WEBHOOK_QATEAM_INCORRECT_TYPE'),
            ],
            'SetDesigners' => [
                'url' => env('DISCORD_WEBHOOK_SETDESIGN_TEAM'),
                'is_forum' => true,
            ],
            'WritingTeam' => [
                'url' => env('DISCORD_WEBHOOK_WRITING_TEAM'),
                'is_forum' => true,
            ],
            'UnlockTeam' => [
                'url' => env('DISCORD_WEBHOOK_UNLOCKTEAM'),
                'is_forum' => true,
            ],
            'QualityQuest' => [
                'url' => env('DISCORD_WEBHOOK_QUALITYQUEST'),
            ],
            'RACheats' => [
                'url' => env('DISCORD_WEBHOOK_RACHEATS'),
                'is_forum' => true,
                'mention_role' => env('DISCORD_ROLE_INVESTIGATOR'),
            ],
            'RAdmin' => [
                'url' => env('DISCORD_WEBHOOK_MOD'),
                'is_forum' => true,
                'mention_role' => [
                    env('DISCORD_ROLE_ADMIN'),
                    env('DISCORD_ROLE_MODERATOR'),
                ],
                'verify_url' => env('DISCORD_WEBHOOK_MOD_VERIFY'),
                'reports_url' => env('DISCORD_WEBHOOK_MOD_REPORTS'),
            ],
            'RAEvents' => [
                'url' => env('DISCORD_WEBHOOK_RAEVENTS'),
                'is_forum' => true,
                'mention_role' => env('DISCORD_ROLE_EVENTS'),
            ],
            'RANews' => [
                'url' => env('DISCORD_WEBHOOK_RANEWS'),
            ],
        ],
        'alerts_webhook' => [
            /**
             * Keys here are automatically inherited from their alert class names.
             *
             * @example "FooBarAlert" -> "foo_bar"
             * @example "SuspiciousBeatTimeAlert" -> "suspicious_beat_time"
             */
            'claim_with_unresolved_tickets' => env('DISCORD_WEBHOOK_ALERT_CLAIM_WITH_UNRESOLVED_TICKETS'),
            'developer_inactivity' => env('DISCORD_WEBHOOK_ALERT_DEVELOPER_INACTIVITY'),
            'inappropriate_game_screenshot' => env('DISCORD_WEBHOOK_ALERT_INAPPROPRIATE_GAME_SCREENSHOT'),
            'muted_user_message' => env('DISCORD_WEBHOOK_ALERT_MUTED_USER_MESSAGE'),
            'suspicious_beat_time' => env('DISCORD_WEBHOOK_ALERT_SUSPICIOUS_BEAT_TIME'),
            'suspicious_connect_warning' => env('DISCORD_WEBHOOK_ALERT_SUSPICIOUS_CONNECT_WARNING'),
        ],
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
    ],

    'github' => [
        'organisation' => env('GITHUB_ORG'),
    ],

    'google' => [
        'recaptcha_key' => env('GOOGLE_RECAPTCHA_KEY'),
        'recaptcha_secret' => env('GOOGLE_RECAPTCHA_SECRET'),
    ],

    'patreon' => [
        'user_id' => env('PATREON_USER_ID'),
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'reddit' => [
        'channel' => env('REDDIT_CHANNEL'),
    ],

    'ses' => [
        'key' => env('AWS_SES_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID')),
        'secret' => env('AWS_SES_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY')),
        'region' => env('AWS_SES_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
    ],

    'twitter' => [
        'channel' => env('TWITTER_CHANNEL'),
    ],

];
