import type { PageProps } from '@inertiajs/core';

import type { User } from './user.model';
import type { UserRole } from './user-role.model';

export interface AppGlobalProps extends PageProps {
  auth: {
    roles: UserRole[];
    user: User;
  } | null;
}
