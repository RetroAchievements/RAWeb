declare namespace App.Data {
  export type User = {
    avatarUrl: string;
    displayName: string;
    id: number;
    legacyPermissions: number;
    preferences: { prefersAbsoluteDates: boolean };
    roles: App.Models.UserRole[];
    unreadMessageCount: number;
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
  export enum AchievementFlag {
    OfficialCore = 3,
    Unofficial = 5,
  }
}
