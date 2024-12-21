import type { PageProps } from '@inertiajs/core';
import type { SetRequired } from 'type-fest';

import { createFactory } from '@/test/createFactory';

import type { ZiggyProps } from './ziggy-props.model';

export type AuthenticatedUser = SetRequired<
  App.Data.User,
  | 'id'
  | 'legacyPermissions'
  | 'points'
  | 'pointsSoftcore'
  | 'preferences'
  | 'roles'
  | 'unreadMessageCount'
  | 'websitePrefs'
>;

export interface AppGlobalProps extends PageProps {
  auth: { user: AuthenticatedUser } | null;

  config: {
    services: {
      patreon: { userId?: string | number };
    };
  };

  ziggy: ZiggyProps;
}

export const createAuthenticatedUser = createFactory<AuthenticatedUser>((faker) => ({
  avatarUrl: faker.internet.url(),
  displayName: faker.internet.displayName(),
  id: faker.number.int({ min: 1, max: 99999 }),
  isMuted: false,
  isNew: false,
  mutedUntil: null,
  legacyPermissions: 8447,
  points: faker.number.int({ min: 0, max: 100000 }),
  pointsSoftcore: faker.number.int({ min: 0, max: 100000 }),
  preferences: {
    prefersAbsoluteDates: false,
    shouldAlwaysBypassContentWarnings: false,
  },
  roles: [],
  unreadMessageCount: 0,
  websitePrefs: 63, // The default when a new account is created.
}));

export const createAppGlobalProps = createFactory<AppGlobalProps>(() => ({
  auth: { user: createAuthenticatedUser() },

  config: { services: { patreon: {} } },

  ziggy: { defaults: [], device: 'desktop', location: '', port: 8080, query: {}, url: '' },
}));
