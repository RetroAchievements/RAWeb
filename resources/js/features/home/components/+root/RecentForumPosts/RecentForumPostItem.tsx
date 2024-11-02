import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';

import { DiffTimestamp } from '@/common/components/DiffTimestamp';
import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';

interface RecentForumPostItemProps {
  post: App.Data.ForumTopic;
}

export const RecentForumPostItem: FC<RecentForumPostItemProps> = ({ post }) => {
  const { auth } = usePageProps();

  const { t } = useLaravelReactI18n();

  const commentId = post.latestComment?.id;
  const postUrl = `/viewtopic.php?t=${post.id}&c=${commentId}#${commentId}`;

  if (!post.latestComment?.user || !post.latestComment?.createdAt || !post.latestComment.body) {
    return null;
  }

  return (
    <div className="rounded bg-embed px-2.5 py-1.5">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-1.5">
          <UserAvatar {...post.latestComment.user} size={16} />

          <span className="smalldate">
            <DiffTimestamp
              at={post.latestComment.createdAt}
              asAbsoluteDate={auth?.user.preferences.prefersAbsoluteDates}
            />
          </span>
        </div>

        <a href={postUrl}>{t('View')}</a>
      </div>

      <p>
        {t('in')} <a href={postUrl}>{post.title}</a>
      </p>

      <p className="line-clamp-1">{post.latestComment.body}</p>
    </div>
  );
};
