import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { EmptyState } from '@/common/components/EmptyState';
import { usePageProps } from '@/common/hooks/usePageProps';

import { HomeHeading } from '../../HomeHeading';
import { SeeMoreLink } from '../../SeeMoreLink';
import { RecentForumPostItem } from './RecentForumPostItem';

export const RecentForumPosts: FC = () => {
  const { recentForumPosts } = usePageProps<App.Http.Data.HomePageProps>();

  const { t } = useTranslation();

  return (
    <div>
      <HomeHeading>{t('Recent Forum Posts')}</HomeHeading>

      {!recentForumPosts?.length ? (
        <div className="rounded bg-embed">
          <EmptyState>{t('No recent forum posts were found.')}</EmptyState>
        </div>
      ) : null}

      {recentForumPosts?.length ? (
        <>
          <div className="flex flex-col gap-y-1">
            {recentForumPosts.map((recentForumPost, index) => (
              <RecentForumPostItem
                key={`post-${recentForumPost.id}-${index}`}
                post={recentForumPost}
              />
            ))}
          </div>

          <SeeMoreLink href={route('forum.recent-posts')} asClientSideRoute={true} />
        </>
      ) : null}
    </div>
  );
};
