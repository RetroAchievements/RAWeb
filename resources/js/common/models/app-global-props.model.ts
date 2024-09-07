import type { PageProps } from '@inertiajs/core';
import type { SetRequired } from 'type-fest';

import { createFactory } from '@/test/createFactory';

type AuthenticatedUser = SetRequired<
  App.Data.User,
  'id' | 'legacyPermissions' | 'preferences' | 'roles' | 'unreadMessageCount' | 'websitePrefs'
>;

export interface AppGlobalProps extends PageProps {
  auth: { user: AuthenticatedUser } | null;
}

export const createAuthenticatedUser = createFactory<AuthenticatedUser>((faker) => ({
  avatarUrl: faker.internet.url(),
  displayName: faker.internet.displayName(),
  id: faker.number.int({ min: 1, max: 99999 }),
  isMuted: false,
  legacyPermissions: 8447,
  preferences: {
    prefersAbsoluteDates: false,
  },
  roles: [],
  unreadMessageCount: 0,
  websitePrefs: 63, // The default when a new account is created.
}));

export const createAppGlobalProps = createFactory<AppGlobalProps>(() => ({
  auth: { user: createAuthenticatedUser() },
}));
