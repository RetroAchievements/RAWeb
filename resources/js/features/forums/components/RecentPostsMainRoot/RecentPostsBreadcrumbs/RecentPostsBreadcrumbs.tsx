import type { FC } from 'react';

import {
  BaseBreadcrumb,
  BaseBreadcrumbItem,
  BaseBreadcrumbLink,
  BaseBreadcrumbList,
  BaseBreadcrumbPage,
  BaseBreadcrumbSeparator,
} from '@/common/components/+vendor/BaseBreadcrumb';

export const RecentPostsBreadcrumbs: FC = () => {
  return (
    <div className="navpath">
      <BaseBreadcrumb>
        <BaseBreadcrumbList>
          <BaseBreadcrumbItem>
            <BaseBreadcrumbLink href="/forum.php">Forum Index</BaseBreadcrumbLink>
          </BaseBreadcrumbItem>

          <BaseBreadcrumbSeparator />

          <BaseBreadcrumbItem>
            <BaseBreadcrumbPage>Recent Posts</BaseBreadcrumbPage>
          </BaseBreadcrumbItem>
        </BaseBreadcrumbList>
      </BaseBreadcrumb>
    </div>
  );
};
