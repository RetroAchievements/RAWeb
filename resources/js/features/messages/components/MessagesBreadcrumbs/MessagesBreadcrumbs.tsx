import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import {
  BaseBreadcrumb,
  BaseBreadcrumbItem,
  BaseBreadcrumbLink,
  BaseBreadcrumbList,
  BaseBreadcrumbPage,
  BaseBreadcrumbSeparator,
} from '@/common/components/+vendor/BaseBreadcrumb';
import { InertiaLink } from '@/common/components/InertiaLink';
import type { TranslatedString } from '@/types/i18next';

interface MessagesBreadcrumbsProps {
  delegatedUserDisplayName?: string;
  t_currentPageLabel?: TranslatedString;
  shouldShowInboxLinkCrumb?: boolean;
}

export const MessagesBreadcrumbs: FC<MessagesBreadcrumbsProps> = ({
  delegatedUserDisplayName,
  t_currentPageLabel,
  shouldShowInboxLinkCrumb = true,
}) => {
  const { t } = useTranslation();

  return (
    <div className="navpath mb-3 hidden sm:block">
      <BaseBreadcrumb>
        <BaseBreadcrumbList>
          {shouldShowInboxLinkCrumb ? (
            <>
              {delegatedUserDisplayName ? (
                <BaseBreadcrumbItem
                  aria-label={t("{{username}}'s Inbox", { username: delegatedUserDisplayName })}
                >
                  <BaseBreadcrumbLink asChild>
                    <InertiaLink
                      href={route('message-thread.user.index', { user: delegatedUserDisplayName })}
                    >
                      {t("{{username}}'s Inbox", { username: delegatedUserDisplayName })}
                    </InertiaLink>
                  </BaseBreadcrumbLink>
                </BaseBreadcrumbItem>
              ) : (
                <BaseBreadcrumbItem aria-label={t('Your Inbox')}>
                  <BaseBreadcrumbLink asChild>
                    <InertiaLink href={route('message-thread.index')}>
                      {t('Your Inbox')}
                    </InertiaLink>
                  </BaseBreadcrumbLink>
                </BaseBreadcrumbItem>
              )}
            </>
          ) : null}

          {t_currentPageLabel ? (
            <>
              {shouldShowInboxLinkCrumb ? <BaseBreadcrumbSeparator /> : null}

              <BaseBreadcrumbItem aria-label={t_currentPageLabel}>
                <BaseBreadcrumbPage>{t_currentPageLabel}</BaseBreadcrumbPage>
              </BaseBreadcrumbItem>
            </>
          ) : null}
        </BaseBreadcrumbList>
      </BaseBreadcrumb>
    </div>
  );
};
