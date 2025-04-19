import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { DiffTimestamp } from '@/common/components/DiffTimestamp';
import { InertiaLink } from '@/common/components/InertiaLink';
import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';

interface RecentForumPostItemProps {
  post: App.Data.ForumTopic;
}

export const RecentForumPostItem: FC<RecentForumPostItemProps> = ({ post }) => {
  const { auth } = usePageProps();

  const { t } = useTranslation();

  const commentId = post.latestComment?.id;
  const postUrl =
    route('forum-topic.show', { topic: post.id, comment: commentId }) + `#${commentId}`;

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

        <InertiaLink href={postUrl}>{t('View')}</InertiaLink>
      </div>

      <p>
        <Trans
          i18nKey="in <1>{{forumTopicTitle}}</1>"
          values={{ forumTopicTitle: post.title }}
          components={{
            1: <InertiaLink href={postUrl} />,
          }}
        />
      </p>

      <p className="line-clamp-1 lg:max-w-[580px] xl:max-w-[816px]">{post.latestComment.body}</p>
    </div>
  );
};
