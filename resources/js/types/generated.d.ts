declare namespace App.Community.Data {
  export type RecentPostsPageProps<TItems = App.Data.ForumTopic> = {
    paginatedTopics: App.Data.PaginatedData<TItems>;
  };
}
declare namespace App.Data {
  export type ForumTopicComment = {
    id: number;
    body: string;
    createdAt: string;
    updatedAt: string | null;
    user: App.Data.User;
    authorized: boolean;
    forumTopicId: number | null;
  };
  export type ForumTopic = {
    id: number;
    title: string;
    createdAt: string;
    latestComment?: App.Data.ForumTopicComment;
    commentCount24h?: number;
    oldestComment24hId?: number;
    commentCount7d?: number;
    oldestComment7dId?: number;
    user: App.Data.User | null;
  };
  export type PaginatedData<TItems> = {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
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
    id?: number;
    username?: string | null;
    legacyPermissions?: number | null;
    preferences?: { prefersAbsoluteDates: boolean };
    roles?: App.Models.UserRole[];
    unreadMessageCount?: number | null;
  };
  export type UserPermissions = {
    manageGameHashes?: boolean;
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
    | 'developer-veteran';
}
declare namespace App.Platform.Data {
  export type Game = {
    id: number;
    title: string;
    badgeUrl?: string;
    forumTopicId?: number;
    system?: App.Platform.Data.System;
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
  export type System = {
    id: number;
    name: string;
    nameFull?: string;
    nameShort?: string;
  };
}
declare namespace App.Platform.Enums {
  export type GameSetType = 'hub' | 'similar-games';
  export type AchievementFlag = 3 | 5;
}
