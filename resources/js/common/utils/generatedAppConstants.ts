/* eslint-disable */
/* generated with `composer types` */
export const UnlockMode = {
    Softcore: 0,
    Hardcore: 1,
} as const;


export const StringifiedUnlockMode = {
    Softcore: '0',
    Hardcore: '1',
} as const;


export const UserPreference = {
    EmailOn_ActivityComment: 0,
    EmailOn_AchievementComment: 1,
    EmailOn_UserWallComment: 2,
    EmailOn_ForumReply: 3,
    EmailOn_Followed: 4,
    EmailOn_PrivateMessage: 5,
    EmailOn_TicketActivity: 6,
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
    Game_OptOutOfAllSubsets: 18,
} as const;


export const StringifiedUserPreference = {
    EmailOn_ActivityComment: '0',
    EmailOn_AchievementComment: '1',
    EmailOn_UserWallComment: '2',
    EmailOn_ForumReply: '3',
    EmailOn_Followed: '4',
    EmailOn_PrivateMessage: '5',
    EmailOn_TicketActivity: '6',
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
    Game_OptOutOfAllSubsets: '18',
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


export const ClaimStatus = {
    Active: 0,
    Complete: 1,
    Dropped: 2,
    InReview: 3,
} as const;


export const StringifiedClaimStatus = {
    Active: '0',
    Complete: '1',
    Dropped: '2',
    InReview: '3',
} as const;


export const ClaimType = {
    Primary: 0,
    Collaboration: 1,
} as const;


export const StringifiedClaimType = {
    Primary: '0',
    Collaboration: '1',
} as const;


export const AwardType = {
    Mastery: 1,
    AchievementUnlocksYield: 2,
    AchievementPointsYield: 3,
    PatreonSupporter: 6,
    CertifiedLegend: 7,
    GameBeaten: 8,
} as const;


export const StringifiedAwardType = {
    Mastery: '1',
    AchievementUnlocksYield: '2',
    AchievementPointsYield: '3',
    PatreonSupporter: '6',
    CertifiedLegend: '7',
    GameBeaten: '8',
} as const;


export const ArticleType = {
    Game: 1,
    Achievement: 2,
    User: 3,
    News: 4,
    Leaderboard: 6,
    AchievementTicket: 7,
    Forum: 8,
    UserModeration: 9,
    GameHash: 10,
    SetClaim: 11,
    GameModification: 12,
} as const;


export const StringifiedArticleType = {
    Game: '1',
    Achievement: '2',
    User: '3',
    News: '4',
    Leaderboard: '6',
    AchievementTicket: '7',
    Forum: '8',
    UserModeration: '9',
    GameHash: '10',
    SetClaim: '11',
    GameModification: '12',
} as const;


export const TicketType = {
    TriggeredAtWrongTime: 1,
    DidNotTrigger: 2,
} as const;


export const StringifiedTicketType = {
    TriggeredAtWrongTime: '1',
    DidNotTrigger: '2',
} as const;


export const SubscriptionSubjectType = {
    ForumTopic: 'ForumTopic',
    UserWall: 'UserWall',
    GameWall: 'GameWall',
    Achievement: 'Achievement',
    GameTickets: 'GameTickets',
    GameAchievements: 'GameAchievements',
} as const;


export const UserGameListType = {
    AchievementSetRequest: 'achievement_set_request',
    Play: 'play',
    Develop: 'develop',
} as const;


export const ClaimSetType = {
    NewSet: 0,
    Revision: 1,
} as const;


export const StringifiedClaimSetType = {
    NewSet: '0',
    Revision: '1',
} as const;

