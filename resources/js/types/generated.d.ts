declare namespace App.Community.Data {
  export type RecentPostsPageProps<TItems = App.Data.ForumTopic> = {
    paginatedTopics: App.Data.PaginatedData<TItems>;
  };
  export type UserGameListPageProps<TItems = App.Platform.Data.GameListEntry> = {
    paginatedGameListEntries: App.Data.PaginatedData<TItems>;
    filterableSystemOptions: Array<App.Platform.Data.System>;
    can: App.Data.UserPermissions;
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
  export type AwardType = 1 | 2 | 3 | 6 | 7 | 8;
  export type TicketType = 1 | 2;
  export type UserGameListType = 'achievement_set_request' | 'play' | 'develop';
}
declare namespace App.Data {
  export type ForumTopicComment = {
    id: number;
    body: string;
    createdAt: string;
    updatedAt: string | null;
    user: App.Data.User | null;
    authorized: boolean;
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
  export type User = {
    displayName: string;
    avatarUrl: string;
    isMuted: boolean;
    id?: number;
    username?: string | null;
    motto?: string;
    legacyPermissions?: number | null;
    preferences?: { prefersAbsoluteDates: boolean };
    roles?: App.Models.UserRole[];
    apiKey?: string | null;
    deleteRequested?: string | null;
    emailAddress?: string | null;
    unreadMessageCount?: number | null;
    userWallActive?: boolean | null;
    visibleRole?: string | null;
    websitePrefs?: number | null;
  };
  export type UserPermissions = {
    develop?: boolean;
    manageGameHashes?: boolean;
    manipulateApiKeys?: boolean;
    updateAvatar?: boolean;
    updateMotto?: boolean;
  };
}
declare namespace App.Enums {
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
    | 17;
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
    | 'developer-veteran';
}
declare namespace App.Platform.Data {
  export type Achievement = {
    id: number;
    title: string;
    badgeUnlockedUrl?: string;
    badgeLockedUrl?: string;
    game?: App.Platform.Data.Game;
    unlockedAt?: string;
    unlockedHardcoreAt?: string;
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
}
declare namespace App.Platform.Enums {
  export type AchievementSetType =
    | 'core'
    | 'bonus'
    | 'specialty'
    | 'exclusive'
    | 'will_be_bonus'
    | 'will_be_specialty'
    | 'will_be_exclusive';
  export type GameSetType = 'hub' | 'similar-games';
  export type ReleasedAtGranularity = 'day' | 'month' | 'year';
}
