import { useLaravelReactI18n } from 'laravel-react-i18n';
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
  const { t } = useLaravelReactI18n();

  return (
    <div className="navpath">
      <BaseBreadcrumb>
        <BaseBreadcrumbList>
          <BaseBreadcrumbItem>
            <BaseBreadcrumbLink href="/forum.php">{t('Forum Index')}</BaseBreadcrumbLink>
          </BaseBreadcrumbItem>

          <BaseBreadcrumbSeparator />

          <BaseBreadcrumbItem>
            <BaseBreadcrumbPage>{t('Recent Posts')}</BaseBreadcrumbPage>
          </BaseBreadcrumbItem>
        </BaseBreadcrumbList>
      </BaseBreadcrumb>
    </div>
  );
};
