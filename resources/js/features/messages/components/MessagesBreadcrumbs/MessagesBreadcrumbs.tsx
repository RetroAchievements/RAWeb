import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseBreadcrumb,
  BaseBreadcrumbItem,
  BaseBreadcrumbLink,
  BaseBreadcrumbList,
  BaseBreadcrumbPage,
  BaseBreadcrumbSeparator,
} from '@/common/components/+vendor/BaseBreadcrumb';
import type { TranslatedString } from '@/types/i18next';

interface MessagesBreadcrumbsProps {
  t_currentPageLabel: TranslatedString;
  user: App.Data.User;
}

export const MessagesBreadcrumbs: FC<MessagesBreadcrumbsProps> = ({ t_currentPageLabel, user }) => {
  const { t } = useTranslation();

  return (
    <div className="navpath mb-3 hidden sm:block">
      <BaseBreadcrumb>
        <BaseBreadcrumbList>
          <BaseBreadcrumbItem aria-label={t('Messages')}>
            <BaseBreadcrumbLink href={route('message-thread.index')}>
              {t('Messages')}
            </BaseBreadcrumbLink>
          </BaseBreadcrumbItem>

          <BaseBreadcrumbSeparator />

          <BaseBreadcrumbItem aria-label={user.displayName}>
            <BaseBreadcrumbLink href={route('user.show', user.displayName)}>
              {user.displayName}
            </BaseBreadcrumbLink>
          </BaseBreadcrumbItem>

          <BaseBreadcrumbSeparator />

          <BaseBreadcrumbItem aria-label={t_currentPageLabel}>
            <BaseBreadcrumbPage>{t_currentPageLabel}</BaseBreadcrumbPage>
          </BaseBreadcrumbItem>
        </BaseBreadcrumbList>
      </BaseBreadcrumb>
    </div>
  );
};
