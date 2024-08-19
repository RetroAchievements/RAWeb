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
    user: App.Data.User | null;
    latestComment?: App.Data.ForumTopicComment;
    commentCount24h?: number;
    oldestComment24hId?: number;
    commentCount7d?: number;
    oldestComment7dId?: number;
  };
  export type __UNSAFE_PaginatedData = {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
    items: Array<any>;
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
    username?: string;
    legacyPermissions?: number;
    preferences?: { prefersAbsoluteDates: boolean };
    roles?: App.Models.UserRole[];
    unreadMessageCount?: number;
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
declare namespace App.Platform.Enums {
  export type AchievementFlag = 3 | 5;
}
