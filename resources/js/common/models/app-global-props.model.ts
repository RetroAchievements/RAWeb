import type { PageProps } from '@inertiajs/core';
import type { SetRequired } from 'type-fest';

type AuthenticatedUser = SetRequired<
  App.Data.User,
  'legacyPermissions' | 'preferences' | 'roles' | 'unreadMessageCount'
>;

export interface AppGlobalProps extends PageProps {
  auth: { user: AuthenticatedUser } | null;
}
