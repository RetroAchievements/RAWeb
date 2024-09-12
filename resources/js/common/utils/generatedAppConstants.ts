/* eslint-disable */
/* generated with `composer types` */
export const UserPreference = {
    EmailOn_ActivityComment: 0,
    EmailOn_AchievementComment: 1,
    EmailOn_UserWallComment: 2,
    EmailOn_ForumReply: 3,
    EmailOn_Followed: 4,
    EmailOn_PrivateMessage: 5,
    EmailOn_Newsletter: 6,
    Site_SuppressMatureContentWarning: 7,
    SiteMsgOn_ActivityComment: 8,
    SiteMsgOn_AchievementComment: 9,
    SiteMsgOn_UserWallComment: 10,
    SiteMsgOn_ForumReply: 11,
    SiteMsgOn_Followed: 12,
    SiteMsgOn_PrivateMessage: 13,
    SiteMsgOn_Newsletter: 14,
    Forum_ShowAbsoluteDates: 15,
    Game_HideMissableIndicators: 16,
    User_OnlyContactFromFollowing: 17,
} as const;


export const StringifiedUserPreference = {
    EmailOn_ActivityComment: '0',
    EmailOn_AchievementComment: '1',
    EmailOn_UserWallComment: '2',
    EmailOn_ForumReply: '3',
    EmailOn_Followed: '4',
    EmailOn_PrivateMessage: '5',
    EmailOn_Newsletter: '6',
    Site_SuppressMatureContentWarning: '7',
    SiteMsgOn_ActivityComment: '8',
    SiteMsgOn_AchievementComment: '9',
    SiteMsgOn_UserWallComment: '10',
    SiteMsgOn_ForumReply: '11',
    SiteMsgOn_Followed: '12',
    SiteMsgOn_PrivateMessage: '13',
    SiteMsgOn_Newsletter: '14',
    Forum_ShowAbsoluteDates: '15',
    Game_HideMissableIndicators: '16',
    User_OnlyContactFromFollowing: '17',
} as const;


export const UserRole = {
    ROOT: 'root',
    ADMINISTRATOR: 'administrator',
    RELEASE_MANAGER: 'release-manager',
    GAME_HASH_MANAGER: 'game-hash-manager',
    DEVELOPER_STAFF: 'developer-staff',
    DEVELOPER: 'developer',
    DEVELOPER_JUNIOR: 'developer-junior',
    ARTIST: 'artist',
    WRITER: 'writer',
    GAME_EDITOR: 'game-editor',
    PLAY_TESTER: 'play-tester',
    MODERATOR: 'moderator',
    FORUM_MANAGER: 'forum-manager',
    TICKET_MANAGER: 'ticket-manager',
    NEWS_MANAGER: 'news-manager',
    EVENT_MANAGER: 'event-manager',
    CHEAT_INVESTIGATOR: 'cheat-investigator',
    FOUNDER: 'founder',
    ARCHITECT: 'architect',
    ENGINEER: 'engineer',
    TEAM_ACCOUNT: 'team-account',
    BETA: 'beta',
    DEVELOPER_VETERAN: 'developer-veteran',
} as const;


export const TicketType = {
    TriggeredAtWrongTime: 1,
    DidNotTrigger: 2,
} as const;


export const StringifiedTicketType = {
    TriggeredAtWrongTime: '1',
    DidNotTrigger: '2',
} as const;

