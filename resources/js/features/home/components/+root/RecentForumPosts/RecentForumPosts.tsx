import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';

import { EmptyState } from '@/common/components/EmptyState';
import { usePageProps } from '@/common/hooks/usePageProps';

import { HomeHeading } from '../../HomeHeading';
import { SeeMoreLink } from '../../SeeMoreLink';
import { RecentForumPostItem } from './RecentForumPostItem';

export const RecentForumPosts: FC = () => {
  const { recentForumPosts } = usePageProps<App.Http.Data.HomePageProps>();

  const { t } = useLaravelReactI18n();

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
            {recentForumPosts.map((recentForumPost) => (
              <RecentForumPostItem key={`post-${recentForumPost.id}`} post={recentForumPost} />
            ))}
          </div>

          <SeeMoreLink href={route('forum.recent-posts')} asClientSideRoute={true} />
        </>
      ) : null}
    </div>
  );
};
