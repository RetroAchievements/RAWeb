declare namespace App.Community.Data {
  export type AchievementChecklistPageProps = {
    player: App.Data.User;
    groups: Array<App.Community.Data.AchievementGroup>;
  };
  export type AchievementCommentsPageProps<TItems = App.Community.Data.Comment> = {
    achievement: App.Platform.Data.Achievement;
    paginatedComments: App.Data.PaginatedData<TItems>;
    isSubscribed: boolean;
    canComment: boolean;
  };
  export type AchievementGroup = {
    header: string;
    achievements: Array<App.Platform.Data.Achievement>;
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
  export type DeveloperFeedPageProps<TItems = App.Community.Data.ActivePlayer> = {
    developer: App.Data.User;
    unlocksContributed: number;
    pointsContributed: number;
    awardsContributed: number;
    leaderboardEntriesContributed: number;
    activePlayers: App.Data.PaginatedData<TItems>;
    recentUnlocks: Array<App.Community.Data.RecentUnlock>;
    recentPlayerBadges: Array<App.Community.Data.RecentPlayerBadge>;
    recentLeaderboardEntries: Array<App.Community.Data.RecentLeaderboardEntry>;
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
  export type Message = {
    body: string;
    createdAt: string;
    author?: App.Data.User;
    sentBy?: App.Data.User | null;
  };
  export type MessageThreadCreatePageProps = {
    toUser: App.Data.User | null;
    message: string | null;
    subject: string | null;
    templateKind: App.Community.Enums.MessageThreadTemplateKind | null;
    senderUserDisplayName: string;
  };
  export type MessageThread = {
    id: number;
    title: string;
    numMessages: number;
    lastMessage?: App.Community.Data.Message;
    isUnread: boolean;
    messages?: Array<App.Community.Data.Message>;
    participants?: Array<App.Data.User>;
  };
  export type MessageThreadIndexPageProps<TItems = App.Community.Data.MessageThread> = {
    paginatedMessageThreads: App.Data.PaginatedData<TItems>;
    unreadMessageCount: number;
    senderUserDisplayName: string;
    selectableInboxDisplayNames: Array<string>;
  };
  export type MessageThreadShowPageProps<TItems = App.Community.Data.Message> = {
    messageThread: App.Community.Data.MessageThread;
    paginatedMessages: App.Data.PaginatedData<TItems>;
    dynamicEntities: App.Community.Data.ShortcodeDynamicEntities;
    canReply: boolean;
    senderUserDisplayName: string;
  };
  export type RecentLeaderboardEntry = {
    leaderboard: App.Platform.Data.Leaderboard;
    leaderboardEntry: App.Platform.Data.LeaderboardEntry;
    game: App.Platform.Data.Game;
    user: App.Data.User;
    submittedAt: string;
  };
  export type RecentPlayerBadge = {
    game: App.Platform.Data.Game;
    awardType: string;
    user: App.Data.User;
    earnedAt: string;
  };
  export type RecentPostsPageProps<TItems = App.Data.ForumTopic> = {
    paginatedTopics: App.Data.PaginatedData<TItems>;
  };
  export type RecentUnlock = {
    achievement: App.Platform.Data.Achievement;
    game: App.Platform.Data.Game;
    user: App.Data.User;
    unlockedAt: string;
    isHardcore: boolean;
  };
  export type ShortcodeDynamicEntities = {
    users: Array<App.Data.User>;
    tickets: Array<App.Platform.Data.Ticket>;
    achievements: Array<App.Platform.Data.Achievement>;
    games: Array<App.Platform.Data.Game>;
    hubs: Array<App.Platform.Data.GameSet>;
    events: Array<App.Platform.Data.Event>;
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
    persistenceCookieName: string;
    persistedViewPreferences: Record<string, any> | null;
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
    displayableRoles: Array<App.Data.Role>;
    requestedUsername: string | null;
  };
}
declare namespace App.Community.Enums {
  export type NewsCategory =
    | 'achievement-set'
    | 'community'
    | 'events'
    | 'guide'
    | 'media'
    | 'site-release-notes'
    | 'technical';
  export type ClaimSetType = 0 | 1;
  export type UserGameListType = 'achievement_set_request' | 'play' | 'develop';
  export type ArticleType = 1 | 2 | 3 | 4 | 6 | 7 | 8 | 9 | 10 | 11 | 12;
  export type MessageThreadTemplateKind =
    | 'achievement-issue'
    | 'manual-unlock'
    | 'misclassification'
    | 'unwelcome-concept'
    | 'writing-error';
  export type AwardType = 1 | 2 | 3 | 6 | 7 | 8 | 9;
  export type ClaimStatus = 0 | 1 | 2 | 3;
  export type ClaimType = 0 | 1;
  export type SubscriptionSubjectType =
    | 'ForumTopic'
    | 'UserWall'
    | 'GameWall'
    | 'Achievement'
    | 'GameTickets'
    | 'GameAchievements';
  export type TicketState = 0 | 1 | 2 | 3 | 'Demoted';
  export type TicketType = 1 | 2;
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
  export type CreateForumTopicPageProps = {
    forum: App.Data.Forum;
  };
  export type CurrentlyOnline = {
    logEntries: Array<number>;
    numCurrentPlayers: number;
    allTimeHighPlayers: number;
    allTimeHighDate: string | null;
  };
  export type EditForumTopicCommentPageProps = {
    forumTopicComment: App.Data.ForumTopicComment;
  };
  export type ForumCategory = {
    id: number;
    title: string;
    description?: string;
    orderColumn?: number;
  };
  export type Forum = {
    id: number;
    title: string;
    description?: string;
    orderColumn?: number;
    category?: App.Data.ForumCategory;
  };
  export type ForumTopicComment = {
    id: number;
    body: string;
    createdAt: string;
    updatedAt: string | null;
    user: App.Data.User | null;
    isAuthorized: boolean;
    forumTopicId: number | null;
    forumTopic?: App.Data.ForumTopic | null;
  };
  export type ForumTopic = {
    id: number;
    title: string;
    createdAt: string;
    forum?: App.Data.Forum | null;
    requiredPermissions?: number | null;
    lockedAt?: string | null;
    pinnedAt?: string | null;
    latestComment?: App.Data.ForumTopicComment | null;
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
    category: App.Community.Enums.NewsCategory | null;
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
  export type Role = {
    id: number;
    name: string;
  };
  export type ShowForumTopicPageProps<TItems = App.Data.ForumTopicComment> = {
    can: App.Data.UserPermissions;
    dynamicEntities: App.Community.Data.ShortcodeDynamicEntities;
    forumTopic: App.Data.ForumTopic;
    isSubscribed: boolean;
    paginatedForumTopicComments: App.Data.PaginatedData<TItems>;
    metaDescription: string;
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
    createdAt?: string | null;
    deleteRequested?: string | null;
    deletedAt?: string | null;
    displayableRoles?: Array<App.Data.Role> | null;
    emailAddress?: string | null;
    id?: number;
    isEmailVerified?: boolean;
    isMuted?: boolean;
    isNew?: boolean;
    lastActivityAt?: string | null;
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
    visibleRole?: App.Data.Role | null;
    websitePrefs?: number | null;
    preferences?: { shouldAlwaysBypassContentWarnings: boolean; prefersAbsoluteDates: boolean };
    roles?: App.Models.UserRole[];
  };
  export type UserPermissions = {
    authorizeForumTopicComments?: boolean;
    createForumTopicComments?: boolean;
    createGameComments?: boolean;
    createGameForumTopic?: boolean;
    createTriggerTicket?: boolean;
    createUsernameChangeRequest?: boolean;
    deleteForumTopic?: boolean;
    develop?: boolean;
    lockForumTopic?: boolean;
    manageEmulators?: boolean;
    manageEvents?: boolean;
    manageForumTopicComments?: boolean;
    manageForumTopics?: boolean;
    manageGameHashes?: boolean;
    manageGames?: boolean;
    manageGameSets?: boolean;
    manipulateApiKeys?: boolean;
    updateAvatar?: boolean;
    updateForumTopic?: boolean;
    updateMotto?: boolean;
  };
}
declare namespace App.Enums {
  export type ClientSupportLevel = 0 | 1 | 2 | 3 | 4;
  export type GameHashCompatibility = 'compatible' | 'incompatible' | 'untested' | 'patch-required';
  export type PlayerGameActivityEventType = 'unlock' | 'rich-presence' | 'custom';
  export type PlayerGameActivitySessionType =
    | 'player-session'
    | 'reconstructed'
    | 'manual-unlock'
    | 'ticket-created';
  export type UserOS = 'Android' | 'iOS' | 'Linux' | 'macOS' | 'Windows';
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
  export type AchievementOfTheWeekProps = {
    currentEventAchievement: App.Platform.Data.EventAchievement;
    doesUserHaveUnlock: boolean;
  };
  export type DownloadsPageProps = {
    allEmulators: Array<App.Platform.Data.Emulator>;
    allPlatforms: Array<App.Platform.Data.Platform>;
    allSystems: Array<App.Platform.Data.System>;
    topSystemIds: Array<number>;
    popularEmulatorsBySystem: number[][];
    userDetectedPlatformId: number | null;
    userSelectedSystemId: number | null;
    can: App.Data.UserPermissions;
  };
  export type HomePageProps<TItems = App.Community.Data.ActivePlayer> = {
    staticData: App.Data.StaticData;
    achievementOfTheWeek: App.Http.Data.AchievementOfTheWeekProps | null;
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
    userCurrentGame: App.Platform.Data.Game | null;
    userCurrentGameMinutesAgo: number | null;
  };
}
declare namespace App.Models {
  export type UserRole =
    | 'root'
    | 'administrator'
    | 'release-manager'
    | 'game-hash-manager'
    | 'dev-compliance'
    | 'quality-assurance'
    | 'code-reviewer'
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
    | 'community-manager'
    | 'developer-retired';
}
declare namespace App.Platform.Data {
  export type Achievement = {
    badgeLockedUrl: string;
    badgeUnlockedUrl: string;
    id: number;
    title: string;
    createdAt?: string;
    description?: string;
    decorator?: string | null;
    developer?: App.Data.User;
    flags?: App.Platform.Enums.AchievementFlag;
    game?: App.Platform.Data.Game;
    orderColumn?: number;
    points?: number;
    pointsWeighted?: number;
    type?: 'progression' | 'win_condition' | 'missable' | null;
    unlockedAt?: string;
    unlockedHardcoreAt?: string;
    unlockHardcorePercentage?: string;
    unlockPercentage?: string;
    unlocksHardcoreTotal?: number;
    unlocksTotal?: number;
  };
  export type AchievementSet = {
    id: number;
    playersTotal: number;
    playersHardcore: number;
    achievementsPublished: number;
    achievementsUnpublished: number;
    pointsTotal: number;
    pointsWeighted: number;
    imageAssetPathUrl: string;
    createdAt: string | null;
    updatedAt: string | null;
    achievements: Array<App.Platform.Data.Achievement>;
  };
  export type AggregateAchievementSetCredits = {
    achievementsAuthors: Array<App.Platform.Data.UserCredits>;
    achievementsMaintainers: Array<App.Platform.Data.UserCredits>;
    achievementsArtwork: Array<App.Platform.Data.UserCredits>;
    achievementsDesign: Array<App.Platform.Data.UserCredits>;
    achievementSetArtwork: Array<App.Platform.Data.UserCredits>;
    achievementsLogic: Array<App.Platform.Data.UserCredits>;
    achievementsTesting: Array<App.Platform.Data.UserCredits>;
    achievementsWriting: Array<App.Platform.Data.UserCredits>;
  };
  export type AwardEarner = {
    user: App.Data.User;
    dateEarned: string;
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
  export type DeveloperInterestPageProps = {
    game: App.Platform.Data.Game;
    developers: Array<App.Data.User>;
  };
  export type Emulator = {
    id: number;
    name: string;
    canDebugTriggers: boolean | null;
    originalName?: string | null;
    hasOfficialSupport?: boolean | null;
    websiteUrl?: string | null;
    documentationUrl?: string | null;
    sourceUrl?: string | null;
    downloadUrl?: string | null;
    downloadX64Url?: string | null;
    downloads?: Array<App.Platform.Data.EmulatorDownload> | null;
    platforms?: Array<App.Platform.Data.Platform> | null;
    systems?: Array<App.Platform.Data.System> | null;
  };
  export type EmulatorDownload = {
    id: number;
    platformId: number;
    label: string | null;
    url: string;
  };
  export type EventAchievement = {
    achievement?: App.Platform.Data.Achievement;
    sourceAchievement?: App.Platform.Data.Achievement | null;
    event?: App.Platform.Data.Event;
    activeFrom?: string;
    activeThrough?: string;
    activeUntil?: string;
    isObfuscated: boolean;
  };
  export type EventAward = {
    id: number;
    eventId: number;
    tierIndex: number;
    label: string;
    pointsRequired: number;
    badgeUrl: string;
    earnedAt: string | null;
    badgeCount?: number;
  };
  export type EventAwardEarnersPageProps<TItems = App.Platform.Data.AwardEarner> = {
    event: App.Platform.Data.Event;
    eventAward: App.Platform.Data.EventAward;
    paginatedUsers: App.Data.PaginatedData<TItems>;
  };
  export type Event = {
    id: number;
    activeFrom: string | null;
    activeThrough: string | null;
    legacyGame?: App.Platform.Data.Game;
    eventAchievements?: Array<App.Platform.Data.EventAchievement>;
    eventAwards?: Array<App.Platform.Data.EventAward>;
    state?: App.Platform.Enums.EventState;
  };
  export type EventShowPageProps = {
    event: App.Platform.Data.Event;
    can: App.Data.UserPermissions;
    hubs: Array<App.Platform.Data.GameSet>;
    followedPlayerCompletions: Array<App.Platform.Data.FollowedPlayerCompletion>;
    playerAchievementChartBuckets: Array<App.Platform.Data.PlayerAchievementChartBucket>;
    numMasters: number;
    topAchievers: Array<App.Platform.Data.GameTopAchiever>;
    playerGame: App.Platform.Data.PlayerGame | null;
    playerGameProgressionAwards: App.Platform.Data.PlayerGameProgressionAwards | null;
  };
  export type FollowedPlayerCompletion = {
    user: App.Data.User;
    playerGame: App.Platform.Data.PlayerGame;
  };
  export type GameAchievementSet = {
    id: number;
    type: App.Platform.Enums.AchievementSetType;
    title: string | null;
    orderColumn: number;
    createdAt: string | null;
    updatedAt: string | null;
    achievementSet: App.Platform.Data.AchievementSet;
  };
  export type GameClaimant = {
    user: App.Data.User;
    claimType: string;
  };
  export type Game = {
    id: number;
    title: string;
    developer?: string;
    publisher?: string;
    genre?: string;
    badgeUrl?: string;
    forumTopicId?: number;
    guideUrl?: string;
    system?: App.Platform.Data.System;
    achievementsPublished?: number;
    pointsTotal?: number;
    pointsWeighted?: number;
    releasedAt?: string | null;
    releasedAtGranularity?: App.Platform.Enums.ReleasedAtGranularity | null;
    playersHardcore?: number;
    playersTotal?: number;
    lastUpdated?: string;
    numVisibleLeaderboards?: number;
    numUnresolvedTickets?: number;
    hasActiveOrInReviewClaims?: boolean;
    imageBoxArtUrl?: string;
    imageIngameUrl?: string;
    imageTitleUrl?: string;
    isSubsetGame?: boolean;
    claimants?: Array<App.Platform.Data.GameClaimant>;
    gameAchievementSets?: Array<App.Platform.Data.GameAchievementSet>;
    releases?: Array<App.Platform.Data.GameRelease>;
  };
  export type GameHash = {
    id: number;
    md5: string;
    name: string | null;
    labels: Array<App.Platform.Data.GameHashLabel>;
    patchUrl: string | null;
    isMultiDisc?: boolean;
  };
  export type GameHashLabel = {
    label: string;
    imgSrc: string | null;
  };
  export type GameHashesPageProps = {
    game: App.Platform.Data.Game;
    hashes: Array<App.Platform.Data.GameHash>;
    incompatibleHashes: Array<App.Platform.Data.GameHash>;
    untestedHashes: Array<App.Platform.Data.GameHash>;
    patchRequiredHashes: Array<App.Platform.Data.GameHash>;
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
    persistenceCookieName: string;
    persistedViewPreferences: Record<string, any> | null;
    defaultDesktopPageSize: number;
  };
  export type GameRelease = {
    id: number;
    releasedAt: string | null;
    releasedAtGranularity: App.Platform.Enums.ReleasedAtGranularity | null;
    title: string;
    region: App.Platform.Enums.GameReleaseRegion | null;
    isCanonicalGameTitle: boolean;
  };
  export type GameSet = {
    id: number;
    type: App.Platform.Enums.GameSetType;
    title: string | null;
    badgeUrl: string | null;
    gameCount: number;
    isEventHub?: boolean;
    linkCount: number;
    updatedAt: string;
    forumTopicId?: number | null;
    game?: App.Platform.Data.Game;
    gameId?: number | null;
    hasMatureContent?: boolean;
  };
  export type GameShowPageProps = {
    aggregateCredits: App.Platform.Data.AggregateAchievementSetCredits | null;
    game: App.Platform.Data.Game;
    can: App.Data.UserPermissions;
    hasMatureContent: Array<App.Platform.Data.GameSet>;
    hubs: Array<any>;
    isOnWantToDevList: boolean;
    isOnWantToPlayList: boolean;
    isSubscribedToComments: boolean;
    followedPlayerCompletions: Array<App.Platform.Data.FollowedPlayerCompletion>;
    playerAchievementChartBuckets: Array<App.Platform.Data.PlayerAchievementChartBucket>;
    numComments: number;
    numCompatibleHashes: number;
    numMasters: number;
    numOpenTickets: number;
    recentVisibleComments: Array<App.Community.Data.Comment>;
    similarGames: Array<App.Platform.Data.Game>;
    topAchievers: Array<App.Platform.Data.GameTopAchiever>;
    playerGame: App.Platform.Data.PlayerGame | null;
    playerGameProgressionAwards: App.Platform.Data.PlayerGameProgressionAwards | null;
    seriesHub: App.Platform.Data.SeriesHub | null;
  };
  export type GameSuggestPageProps<TItems = App.Platform.Data.GameSuggestionEntry> = {
    paginatedGameListEntries: App.Data.PaginatedData<TItems>;
    sourceGame: App.Platform.Data.Game | null;
    defaultDesktopPageSize: number;
  };
  export type GameSuggestionContext = {
    relatedGame: App.Platform.Data.Game | null;
    relatedGameSet: App.Platform.Data.GameSet | null;
    sourceGameKind: App.Platform.Services.GameSuggestions.Enums.SourceGameKind | null;
    relatedAuthor: App.Data.User | null;
  };
  export type GameSuggestionEntry = {
    suggestionReason: App.Platform.Enums.GameSuggestionReason;
    suggestionContext: App.Platform.Data.GameSuggestionContext | null;
    game: App.Platform.Data.Game;
    playerGame: App.Platform.Data.PlayerGame | null;
    isInBacklog: boolean | null;
  };
  export type GameTopAchiever = {
    userDisplayName: string;
    userAvatarUrl: string;
    achievementsUnlockedHardcore: number;
    pointsHardcore: number;
    lastUnlockHardcoreAt: string;
    beatenHardcoreAt: string | null;
  };
  export type GameTopAchieversPageProps<TItems = App.Platform.Data.RankedGameTopAchiever> = {
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
    persistenceCookieName: string;
    persistedViewPreferences: Record<string, any> | null;
    defaultDesktopPageSize: number;
  };
  export type Leaderboard = {
    id: number;
    title: string;
    description?: string;
    game?: App.Platform.Data.Game;
  };
  export type LeaderboardEntry = {
    id: number;
    score?: number;
    formattedScore?: string;
    createdAt?: string;
  };
  export type ParsedUserAgent = {
    client: string;
    clientVersion: string;
    os: string | null;
    integrationVersion: string | null;
    extra: Array<any> | null;
    clientVariation: string | null;
  };
  export type Platform = {
    id: number;
    name: string;
    executionEnvironment: App.Platform.Enums.PlatformExecutionEnvironment | null;
    orderColumn: number;
  };
  export type PlayerAchievementChartBucket = {
    start: number;
    end: number;
    softcore: number;
    hardcore: number;
  };
  export type PlayerBadge = {
    awardType: number;
    awardData: number;
    awardDataExtra: number;
    awardDate: string;
  };
  export type PlayerGameActivity = {
    summarizedActivity: App.Platform.Data.PlayerGameActivitySummary;
    sessions: Array<App.Platform.Data.PlayerGameActivitySession>;
    clientBreakdown: Array<App.Platform.Data.PlayerGameClientBreakdown>;
  };
  export type PlayerGameActivityEvent = {
    type: App.Enums.PlayerGameActivityEventType;
    description: string | null;
    header: string | null;
    when: string | null;
    id: number | null;
    hardcore: boolean | null;
    achievement: App.Platform.Data.Achievement | null;
    unlocker: App.Data.User | null;
    hardcoreLater: boolean | null;
  };
  export type PlayerGameActivityPageProps = {
    player: App.Data.User;
    game: App.Platform.Data.Game;
    playerGame: App.Platform.Data.PlayerGame | null;
    activity: App.Platform.Data.PlayerGameActivity;
  };
  export type PlayerGameActivitySession = {
    type: App.Enums.PlayerGameActivitySessionType;
    startTime: string;
    endTime: string;
    duration: number;
    userAgent: string | null;
    parsedUserAgent: App.Platform.Data.ParsedUserAgent | null;
    gameHash: App.Platform.Data.GameHash | null;
    events: Array<App.Platform.Data.PlayerGameActivityEvent>;
  };
  export type PlayerGameActivitySummary = {
    achievementPlaytime: number;
    achievementSessionCount: number;
    generatedSessionAdjustment: number;
    totalUnlockTime: number;
    totalPlaytime: number;
  };
  export type PlayerGameClientBreakdown = {
    clientIdentifier: string;
    agents: Array<any>;
    duration: number;
    durationPercentage: number;
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
  export type PlayerGameProgressionAwards = {
    beatenSoftcore: App.Platform.Data.PlayerBadge | null;
    beatenHardcore: App.Platform.Data.PlayerBadge | null;
    completed: App.Platform.Data.PlayerBadge | null;
    mastered: App.Platform.Data.PlayerBadge | null;
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
  export type RankedGameTopAchiever = {
    rank: number;
    user: App.Data.User;
    score: number;
    badge: App.Platform.Data.PlayerBadge | null;
  };
  export type ReportAchievementIssuePageProps = {
    achievement: App.Platform.Data.Achievement;
    hasSession: boolean;
    ticketType: number;
    extra: string | null;
    can: App.Data.UserPermissions;
  };
  export type SeriesHub = {
    hub: App.Platform.Data.GameSet;
    totalGameCount: number;
    gamesWithAchievementsCount: number;
    achievementsPublished: number;
    pointsTotal: number;
  };
  export type System = {
    id: number;
    name: string;
    active?: boolean;
    manufacturer?: string;
    nameFull?: string;
    nameShort?: string;
    iconUrl?: string;
  };
  export type SystemGameListPageProps<TItems = App.Platform.Data.GameListEntry> = {
    system: App.Platform.Data.System;
    paginatedGameListEntries: App.Data.PaginatedData<TItems>;
    can: App.Data.UserPermissions;
    persistenceCookieName: string;
    persistedViewPreferences: Record<string, any> | null;
    defaultDesktopPageSize: number;
  };
  export type Ticket = {
    id: number;
    ticketableType: App.Platform.Enums.TicketableType;
    state?: number;
    ticketable?:
      | App.Platform.Data.Achievement
      | App.Platform.Data.Leaderboard
      | App.Platform.Data.Game;
  };
  export type UserCredits = {
    displayName: string;
    avatarUrl: string;
    count: number;
    dateCredited: string | null;
    deletedAt?: string | null;
  };
}
declare namespace App.Platform.Enums {
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
  export type EventState = 'active' | 'concluded' | 'evergreen';
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
  export type GameListSetTypeFilterValue = 'only-games' | 'only-subsets';
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
  export type GameReleaseRegion =
    | 'as'
    | 'au'
    | 'br'
    | 'ch'
    | 'eu'
    | 'jp'
    | 'kr'
    | 'nz'
    | 'na'
    | 'worldwide'
    | 'other';
  export type GameSetRolePermission = 'view' | 'update';
  export type GameSetType = 'hub' | 'similar-games';
  export type GameSuggestionReason =
    | 'common-players'
    | 'random'
    | 'revised'
    | 'shared-author'
    | 'shared-hub'
    | 'similar-game'
    | 'want-to-play';
  export type PlatformExecutionEnvironment =
    | 'desktop'
    | 'mobile'
    | 'console'
    | 'single_board'
    | 'original_hardware'
    | 'embedded'
    | 'web';
  export type PlayerPreferredMode = 'softcore' | 'hardcore' | 'mixed';
  export type TicketableType = 'achievement' | 'leaderboard' | 'rich-presence';
  export type UnlockMode = 0 | 1;
  export type ReleasedAtGranularity = 'day' | 'month' | 'year';
  export type TriggerableType = 'achievement' | 'leaderboard' | 'game';
}
declare namespace App.Platform.Services.GameSuggestions.Enums {
  export type SourceGameKind = 'beaten' | 'mastered' | 'want-to-play';
}
