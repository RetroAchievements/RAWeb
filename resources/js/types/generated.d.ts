declare namespace App.Community.Data {
  export type AchievementCommentsPageProps<TItems = App.Community.Data.Comment> = {
    achievement: App.Platform.Data.Achievement;
    paginatedComments: App.Data.PaginatedData<TItems>;
    isSubscribed: boolean;
    canComment: boolean;
  };
  export type ActivePlayer = {
    user: App.Data.User;
    game: App.Platform.Data.Game;
  };
  export type Comment = {
    id: number;
    commentableId: number;
    commentableType: number;
    payload: string;
    createdAt: string;
    updatedAt: string | null;
    user: App.Data.User;
    canDelete: boolean;
    isAutomated: boolean;
  };
  export type GameClaimsCommentsPageProps<TItems = App.Community.Data.Comment> = {
    game: App.Platform.Data.Game;
    paginatedComments: App.Data.PaginatedData<TItems>;
    isSubscribed: boolean;
    canComment: boolean;
  };
  export type GameCommentsPageProps<TItems = App.Community.Data.Comment> = {
    game: App.Platform.Data.Game;
    paginatedComments: App.Data.PaginatedData<TItems>;
    isSubscribed: boolean;
    canComment: boolean;
  };
  export type GameHashesCommentsPageProps<TItems = App.Community.Data.Comment> = {
    game: App.Platform.Data.Game;
    paginatedComments: App.Data.PaginatedData<TItems>;
    isSubscribed: boolean;
    canComment: boolean;
  };
  export type GameModificationsCommentsPageProps<TItems = App.Community.Data.Comment> = {
    game: App.Platform.Data.Game;
    paginatedComments: App.Data.PaginatedData<TItems>;
    isSubscribed: boolean;
    canComment: boolean;
  };
  export type LeaderboardCommentsPageProps<TItems = App.Community.Data.Comment> = {
    leaderboard: App.Platform.Data.Leaderboard;
    paginatedComments: App.Data.PaginatedData<TItems>;
    isSubscribed: boolean;
    canComment: boolean;
  };
  export type RecentPostsPageProps<TItems = App.Data.ForumTopic> = {
    paginatedTopics: App.Data.PaginatedData<TItems>;
  };
  export type Subscription = {
    id: number;
    subjectType: App.Community.Enums.SubscriptionSubjectType;
    subjectId: number;
    state: boolean;
    user?: App.Data.User;
  };
  export type TrendingGame = {
    game: App.Platform.Data.Game;
    playerCount: number;
  };
  export type UserCommentsPageProps<TItems = App.Community.Data.Comment> = {
    targetUser: App.Data.User;
    paginatedComments: App.Data.PaginatedData<TItems>;
    isSubscribed: boolean;
    canComment: boolean;
  };
  export type UserGameListPageProps<TItems = App.Platform.Data.GameListEntry> = {
    paginatedGameListEntries: App.Data.PaginatedData<TItems>;
    filterableSystemOptions: Array<App.Platform.Data.System>;
    can: App.Data.UserPermissions;
    defaultDesktopPageSize: number;
  };
  export type UserModerationCommentsPageProps<TItems = App.Community.Data.Comment> = {
    targetUser: App.Data.User;
    paginatedComments: App.Data.PaginatedData<TItems>;
    isSubscribed: boolean;
    canComment: boolean;
  };
  export type UserRecentPostsPageProps<TItems = App.Data.ForumTopic> = {
    targetUser: App.Data.User;
    paginatedTopics: App.Data.PaginatedData<TItems>;
  };
  export type UserSettingsPageProps = {
    userSettings: App.Data.User;
    can: App.Data.UserPermissions;
  };
}
declare namespace App.Community.Enums {
  export type ArticleType = 1 | 2 | 3 | 4 | 6 | 7 | 8 | 9 | 10 | 11 | 12;
  export type AwardType = 1 | 2 | 3 | 6 | 7 | 8;
  export type ClaimSetType = 0 | 1;
  export type ClaimStatus = 0 | 1 | 2 | 3;
  export type ClaimType = 0 | 1;
  export type SubscriptionSubjectType =
    | 'ForumTopic'
    | 'UserWall'
    | 'GameWall'
    | 'Achievement'
    | 'GameTickets'
    | 'GameAchievements';
  export type TicketType = 1 | 2;
  export type UserGameListType = 'achievement_set_request' | 'play' | 'develop';
}
declare namespace App.Data {
  export type AchievementSetClaim = {
    id: number;
    users: Array<App.Data.User>;
    game: App.Platform.Data.Game;
    claimType: number;
    setType: number;
    status: number;
    created: string;
    finished: string;
  };
  export type CurrentlyOnline = {
    logEntries: Array<number>;
    numCurrentPlayers: number;
    allTimeHighPlayers: number;
    allTimeHighDate: string | null;
  };
  export type ForumTopicComment = {
    id: number;
    body: string;
    createdAt: string;
    updatedAt: string | null;
    user: App.Data.User | null;
    isAuthorized: boolean;
    forumTopicId: number | null;
  };
  export type ForumTopic = {
    id: number;
    title: string;
    createdAt: string;
    latestComment?: App.Data.ForumTopicComment;
    commentCount24h?: number | null;
    oldestComment24hId?: number | null;
    commentCount7d?: number | null;
    oldestComment7dId?: number | null;
    user: App.Data.User | null;
  };
  export type News = {
    id: number;
    createdAt: string;
    title: string;
    lead: string | null;
    body: string;
    user: App.Data.User;
    link: string | null;
    imageAssetPath: string | null;
    publishAt: string | null;
    unpublishAt: string | null;
    pinnedAt: string | null;
  };
  export type PaginatedData<TItems> = {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
    unfilteredTotal: number | null;
    items: TItems[];
    links: {
      firstPageUrl: string | null;
      lastPageUrl: string | null;
      previousPageUrl: string | null;
      nextPageUrl: string | null;
    };
  };
  export type StaticData = {
    numGames: number;
    numAchievements: number;
    numHardcoreMasteryAwards: number;
    numHardcoreGameBeatenAwards: number;
    numRegisteredUsers: number;
    numAwarded: number;
    totalPointsEarned: number;
    eventAotwForumId: number | null;
  };
  export type StaticGameAward = {
    game: App.Platform.Data.Game;
    user: App.Data.User;
    awardedAt: string;
  };
  export type User = {
    displayName: string;
    avatarUrl: string;
    apiKey?: string | null;
    deleteRequested?: string | null;
    deletedAt?: string | null;
    emailAddress?: string | null;
    id?: number;
    isMuted?: boolean;
    isNew?: boolean;
    legacyPermissions?: number | null;
    locale?: string | null;
    motto?: string;
    mutedUntil?: string | null;
    playerPreferredMode?: App.Platform.Enums.PlayerPreferredMode;
    points?: number;
    pointsSoftcore?: number;
    richPresenceMsg?: string | null;
    unreadMessageCount?: number | null;
    username?: string | null;
    userWallActive?: boolean | null;
    visibleRole?: string | null;
    websitePrefs?: number | null;
    preferences?: { prefersAbsoluteDates: boolean };
    roles?: App.Models.UserRole[];
  };
  export type UserPermissions = {
    develop?: boolean;
    manageGameHashes?: boolean;
    manageGameSets?: boolean;
    manipulateApiKeys?: boolean;
    updateAvatar?: boolean;
    updateMotto?: boolean;
  };
}
declare namespace App.Enums {
  export type ClientSupportLevel = 0 | 1 | 2 | 3;
  export type UserPreference =
    | 0
    | 1
    | 2
    | 3
    | 4
    | 5
    | 6
    | 7
    | 8
    | 9
    | 10
    | 11
    | 12
    | 13
    | 14
    | 15
    | 16
    | 17
    | 18;
}
declare namespace App.Http.Data {
  export type HomePageProps<TItems = App.Community.Data.ActivePlayer> = {
    staticData: App.Data.StaticData;
    achievementOfTheWeek: App.Platform.Data.EventAchievement | null;
    mostRecentGameMastered: App.Data.StaticGameAward | null;
    mostRecentGameBeaten: App.Data.StaticGameAward | null;
    recentNews: Array<App.Data.News>;
    completedClaims: Array<App.Data.AchievementSetClaim>;
    currentlyOnline: App.Data.CurrentlyOnline;
    activePlayers: App.Data.PaginatedData<TItems>;
    trendingGames: Array<App.Community.Data.TrendingGame>;
    newClaims: Array<App.Data.AchievementSetClaim>;
    recentForumPosts: Array<App.Data.ForumTopic>;
    persistedActivePlayersSearch: string | null;
  };
}
declare namespace App.Models {
  export type UserRole =
    | 'root'
    | 'administrator'
    | 'release-manager'
    | 'game-hash-manager'
    | 'developer-staff'
    | 'developer'
    | 'developer-junior'
    | 'artist'
    | 'writer'
    | 'game-editor'
    | 'play-tester'
    | 'moderator'
    | 'forum-manager'
    | 'ticket-manager'
    | 'news-manager'
    | 'event-manager'
    | 'cheat-investigator'
    | 'founder'
    | 'architect'
    | 'engineer'
    | 'team-account'
    | 'beta'
    | 'developer-retired';
}
declare namespace App.Platform.Data {
  export type Achievement = {
    id: number;
    title: string;
    description?: string;
    badgeUnlockedUrl?: string;
    badgeLockedUrl?: string;
    game?: App.Platform.Data.Game;
    unlockedAt?: string;
    unlockedHardcoreAt?: string;
    points?: number;
    pointsWeighted?: number;
  };
  export type CreateAchievementTicketPageProps = {
    achievement: App.Platform.Data.Achievement;
    emulators: Array<App.Platform.Data.Emulator>;
    gameHashes: Array<App.Platform.Data.GameHash>;
    selectedEmulator: string | null;
    selectedGameHashId: number | null;
    emulatorVersion: string | null;
    emulatorCore: string | null;
    selectedMode: number | null;
  };
  export type Emulator = {
    id: number;
    name: string;
  };
  export type EventAchievement = {
    achievement?: App.Platform.Data.Achievement;
    sourceAchievement?: App.Platform.Data.Achievement;
    activeUntil?: string;
    forumTopicId?: number;
  };
  export type Game = {
    id: number;
    title: string;
    badgeUrl?: string;
    forumTopicId?: number;
    system?: App.Platform.Data.System;
    achievementsPublished?: number;
    pointsTotal?: number;
    pointsWeighted?: number;
    releasedAt?: string | null;
    releasedAtGranularity?: string | null;
    playersTotal?: number;
    lastUpdated?: string;
    numVisibleLeaderboards?: number;
    numUnresolvedTickets?: number;
    hasActiveOrInReviewClaims?: boolean;
  };
  export type GameHash = {
    id: number;
    md5: string;
    name: string | null;
    labels: Array<App.Platform.Data.GameHashLabel>;
    patchUrl: string | null;
  };
  export type GameHashLabel = {
    label: string;
    imgSrc: string | null;
  };
  export type GameHashesPageProps = {
    game: App.Platform.Data.Game;
    hashes: Array<App.Platform.Data.GameHash>;
    can: App.Data.UserPermissions;
  };
  export type GameListEntry = {
    game: App.Platform.Data.Game;
    playerGame: App.Platform.Data.PlayerGame | null;
    isInBacklog: boolean | null;
  };
  export type GameListPageProps<TItems = App.Platform.Data.GameListEntry> = {
    paginatedGameListEntries: App.Data.PaginatedData<TItems>;
    filterableSystemOptions: Array<App.Platform.Data.System>;
    can: App.Data.UserPermissions;
    defaultDesktopPageSize: number;
  };
  export type GameSet = {
    id: number;
    type: App.Platform.Enums.GameSetType;
    title: string | null;
    badgeUrl: string | null;
    gameCount: number;
    linkCount: number;
    updatedAt: string;
  };
  export type GameTopAchiever = {
    rank: number;
    user: App.Data.User;
    score: number;
    badge: App.Platform.Data.PlayerBadge | null;
  };
  export type GameTopAchieversPageProps<TItems = App.Platform.Data.GameTopAchiever> = {
    game: App.Platform.Data.Game;
    paginatedUsers: App.Data.PaginatedData<TItems>;
  };
  export type HubPageProps<TItems = App.Platform.Data.GameListEntry> = {
    hub: App.Platform.Data.GameSet;
    paginatedGameListEntries: App.Data.PaginatedData<TItems>;
    filterableSystemOptions: Array<App.Platform.Data.System>;
    can: App.Data.UserPermissions;
    breadcrumbs: Array<App.Platform.Data.GameSet>;
    relatedHubs: Array<App.Platform.Data.GameSet>;
    defaultDesktopPageSize: number;
  };
  export type Leaderboard = {
    id: number;
    title: string;
    description?: string;
    game?: App.Platform.Data.Game;
  };
  export type PlayerBadge = {
    awardType: number;
    awardData: number;
    awardDataExtra: number;
    awardDate: string;
  };
  export type PlayerGame = {
    achievementsUnlocked: number | null;
    achievementsUnlockedHardcore: number | null;
    achievementsUnlockedSoftcore: number | null;
    beatenAt: string | null;
    beatenHardcoreAt: string | null;
    completedAt: string | null;
    completedHardcoreAt: string | null;
    points: number | null;
    pointsHardcore: number | null;
    highestAward?: App.Platform.Data.PlayerBadge | null;
  };
  export type PlayerResettableGameAchievement = {
    id: number;
    title: string;
    points: number;
    isHardcore: boolean;
  };
  export type PlayerResettableGame = {
    id: number;
    title: string;
    consoleName: string;
    numAwarded: number;
    numPossible: number;
  };
  export type ReportAchievementIssuePageProps = {
    achievement: App.Platform.Data.Achievement;
    hasSession: boolean;
    ticketType: number;
    extra: string | null;
  };
  export type System = {
    id: number;
    name: string;
    nameFull?: string;
    nameShort?: string;
    iconUrl?: string;
  };
  export type SystemGameListPageProps<TItems = App.Platform.Data.GameListEntry> = {
    system: App.Platform.Data.System;
    paginatedGameListEntries: App.Data.PaginatedData<TItems>;
    can: App.Data.UserPermissions;
    defaultDesktopPageSize: number;
  };
}
declare namespace App.Platform.Enums {
  export type UnlockMode = 0 | 1;
  export type AchievementAuthorTask = 'artwork' | 'design' | 'logic' | 'testing' | 'writing';
  export type AchievementFlag = 3 | 5;
  export type AchievementSetAuthorTask = 'artwork';
  export type AchievementSetType =
    | 'core'
    | 'bonus'
    | 'specialty'
    | 'exclusive'
    | 'will_be_bonus'
    | 'will_be_specialty'
    | 'will_be_exclusive';
  export type GameListProgressFilterValue =
    | 'unstarted'
    | 'unfinished'
    | 'gte_beaten_softcore'
    | 'gte_beaten_hardcore'
    | 'eq_beaten_softcore'
    | 'eq_beaten_hardcore'
    | 'gte_completed'
    | 'eq_completed'
    | 'eq_mastered'
    | 'revised'
    | 'neq_mastered';
  export type GameListSortField =
    | 'title'
    | 'system'
    | 'achievementsPublished'
    | 'hasActiveOrInReviewClaims'
    | 'pointsTotal'
    | 'retroRatio'
    | 'lastUpdated'
    | 'releasedAt'
    | 'playersTotal'
    | 'numVisibleLeaderboards'
    | 'numUnresolvedTickets'
    | 'progress';
  export type GameSetType = 'hub' | 'similar-games';
  export type PlayerPreferredMode = 'softcore' | 'hardcore' | 'mixed';
  export type ReleasedAtGranularity = 'day' | 'month' | 'year';
}
