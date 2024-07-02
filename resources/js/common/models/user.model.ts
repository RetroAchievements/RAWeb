interface UserPreferences {
  prefersAbsoluteDates: boolean;
}

export interface User {
  avatarUrl: string;
  displayName: string;
  id: number;
  legacyPermissions: number;
  preferences: UserPreferences;
  unreadMessageCount: number;
}
