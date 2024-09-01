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
}
declare namespace App.Platform.Enums {
  export type GameSetType = 'hub' | 'similar-games';
  export type AchievementFlag = 3 | 5;
}
