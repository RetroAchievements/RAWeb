import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import type { TranslatedString } from '@/types/i18next';

import {
  BaseBreadcrumb,
  BaseBreadcrumbItem,
  BaseBreadcrumbLink,
  BaseBreadcrumbList,
  BaseBreadcrumbPage,
  BaseBreadcrumbSeparator,
} from '../+vendor/BaseBreadcrumb';
import { InertiaLink } from '../InertiaLink';

interface ForumBreadcrumbsProps {
  t_currentPageLabel: TranslatedString;

  forum?: App.Data.Forum | null;
  forumCategory?: App.Data.ForumCategory | null;
  forumTopic?: App.Data.ForumTopic | null;
}

export const ForumBreadcrumbs: FC<ForumBreadcrumbsProps> = ({
  forum,
  forumCategory,
  forumTopic,
  t_currentPageLabel,
}) => {
  const { t } = useTranslation();

  return (
    <div className="navpath mb-3 hidden sm:block">
      <BaseBreadcrumb>
        <BaseBreadcrumbList>
          <BaseBreadcrumbItem aria-label={t('Forum Index')}>
            <BaseBreadcrumbLink href="/forum.php">{t('Forum Index')}</BaseBreadcrumbLink>
          </BaseBreadcrumbItem>

          {forumCategory ? (
            <>
              <BaseBreadcrumbSeparator />
              <BaseBreadcrumbItem aria-label={forumCategory.title}>
                <BaseBreadcrumbLink href={`/forum.php?c=${forumCategory.id}`}>
                  {forumCategory.title}
                </BaseBreadcrumbLink>
              </BaseBreadcrumbItem>
            </>
          ) : null}

          {forum ? (
            <>
              <BaseBreadcrumbSeparator />
              <BaseBreadcrumbItem aria-label={forum.title}>
                <BaseBreadcrumbLink href={`/viewforum.php?f=${forum.id}`}>
                  {forum.title}
                </BaseBreadcrumbLink>
              </BaseBreadcrumbItem>
            </>
          ) : null}

          {forumTopic ? (
            <>
              <BaseBreadcrumbSeparator />
              <BaseBreadcrumbItem aria-label={forumTopic.title}>
                <BaseBreadcrumbLink asChild>
                  <InertiaLink href={route('forum-topic.show', { topic: forumTopic.id })}>
                    {forumTopic.title}
                  </InertiaLink>
                </BaseBreadcrumbLink>
              </BaseBreadcrumbItem>
            </>
          ) : null}

          <BaseBreadcrumbSeparator />

          <BaseBreadcrumbItem aria-label={t_currentPageLabel}>
            <BaseBreadcrumbPage>{t_currentPageLabel}</BaseBreadcrumbPage>
          </BaseBreadcrumbItem>
        </BaseBreadcrumbList>
      </BaseBreadcrumb>
    </div>
  );
};
