import type { FC } from 'react';

import {
  BaseBreadcrumb,
  BaseBreadcrumbItem,
  BaseBreadcrumbLink,
  BaseBreadcrumbList,
  BaseBreadcrumbPage,
  BaseBreadcrumbSeparator,
} from '@/common/components/+vendor/BaseBreadcrumb';

interface UserBreadcrumbsProps {
  currentPageLabel: string;

  user?: App.Data.User;
}

export const UserBreadcrumbs: FC<UserBreadcrumbsProps> = ({ currentPageLabel, user }) => {
  return (
    <div className="navpath mb-3 hidden sm:block">
      <BaseBreadcrumb>
        <BaseBreadcrumbList>
          <BaseBreadcrumbItem>
            <BaseBreadcrumbLink href="/userList.php">All Users</BaseBreadcrumbLink>
          </BaseBreadcrumbItem>

          {user ? (
            <>
              <BaseBreadcrumbSeparator />

              <BaseBreadcrumbItem>
                <BaseBreadcrumbLink href={route('user.show', { user: user.displayName })}>
                  {user.displayName}
                </BaseBreadcrumbLink>
              </BaseBreadcrumbItem>
            </>
          ) : null}

          <BaseBreadcrumbSeparator />

          <BaseBreadcrumbItem>
            <BaseBreadcrumbPage>{currentPageLabel}</BaseBreadcrumbPage>
          </BaseBreadcrumbItem>
        </BaseBreadcrumbList>
      </BaseBreadcrumb>
    </div>
  );
};
