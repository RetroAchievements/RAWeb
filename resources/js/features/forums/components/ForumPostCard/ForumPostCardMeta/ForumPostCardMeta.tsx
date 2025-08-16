import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';

import { CommentMetaChip } from '../CommentMetaChip';
import { ForumPostCardTimestamps } from './ForumPostCardTimestamps';

interface ForumPostCardMetaProps {
  comment: App.Data.ForumTopicComment;
  topic: App.Data.ForumTopic;
}

export const ForumPostCardMeta: FC<ForumPostCardMetaProps> = ({ comment, topic }) => {
  const { auth, can } = usePageProps<App.Data.ShowForumTopicPageProps>();
  const { t } = useTranslation();

  const canShowUnverifiedChip =
    !comment.isAuthorized &&
    (comment.user?.displayName === auth?.user.displayName || can.authorizeForumTopicComments);

  const isOriginalPoster = topic.user?.displayName === comment.user?.displayName;

  return (
    <div className="flex items-center gap-x-2">
      {canShowUnverifiedChip ? (
        <BaseTooltip>
          <BaseTooltipTrigger>
            <CommentMetaChip>{t('Unverified')}</CommentMetaChip>
          </BaseTooltipTrigger>

          <BaseTooltipContent className="max-w-80 text-balance text-center">
            <span className="text-xs">
              {t(
                'Not yet visible to the public. Please wait for a moderator to authorize this comment.',
              )}
            </span>
          </BaseTooltipContent>
        </BaseTooltip>
      ) : null}

      {isOriginalPoster ? (
        <BaseTooltip>
          <BaseTooltipTrigger>
            <CommentMetaChip>{t('OP')}</CommentMetaChip>
          </BaseTooltipTrigger>

          <BaseTooltipContent className="text-xs">{t('Original poster')}</BaseTooltipContent>
        </BaseTooltip>
      ) : null}

      <ForumPostCardTimestamps comment={comment} />

      {comment.sentBy ? (
        <>
          <span className="text-neutral-400/80 light:text-neutral-700">{'·'}</span>

          <span className="flex items-center gap-1.5 text-2xs text-neutral-400/80 light:text-neutral-700">
            <Trans
              i18nKey="Posted by <1>{{username}}</1>"
              values={{ username: comment.sentBy.displayName }}
              components={{ 1: <UserAvatar {...comment.sentBy} showLabel={false} size={16} /> }}
            />
          </span>
        </>
      ) : null}

      {comment.editedBy && comment.editedBy.displayName !== comment.sentBy?.displayName ? (
        <>
          <span className="text-neutral-400/80 light:text-neutral-700">{'·'}</span>

          <span className="flex items-center gap-1.5 text-2xs text-neutral-400/80 light:text-neutral-700">
            <Trans
              i18nKey="Edited by <1>{{username}}</1>"
              values={{ username: comment.editedBy.displayName }}
              components={{ 1: <UserAvatar {...comment.editedBy} showLabel={false} size={16} /> }}
            />
          </span>
        </>
      ) : null}
    </div>
  );
};
