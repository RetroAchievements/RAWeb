import type { PageProps } from '@inertiajs/core';

export interface AppGlobalProps extends PageProps {
  auth: { user: App.Data.User } | null;
}
