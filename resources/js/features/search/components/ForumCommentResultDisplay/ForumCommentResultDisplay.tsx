import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { LuMessageSquare } from 'react-icons/lu';

import { useDiffForHumans } from '@/common/utils/l10n/useDiffForHumans';

interface ForumCommentResultDisplayProps {
  forumComment: App.Data.ForumTopicComment;
}

export const ForumCommentResultDisplay: FC<ForumCommentResultDisplayProps> = ({ forumComment }) => {
  const { t } = useTranslation();

  const { diffForHumans } = useDiffForHumans();

  const maxBodyLength = 180;
  const cleanBody =
    forumComment.body.length > maxBodyLength
      ? forumComment.body.substring(0, maxBodyLength) + '...'
      : forumComment.body;

  return (
    <div className="flex w-full items-start gap-3">
      {forumComment.user ? (
        <img
          src={forumComment.user.avatarUrl}
          alt={forumComment.user.displayName}
          className="size-10 rounded"
        />
      ) : (
        <div className="flex size-10 items-center justify-center rounded bg-neutral-700 light:bg-neutral-200">
          <LuMessageSquare className="size-5 text-neutral-400" />
        </div>
      )}

      <div className="flex min-w-0 flex-1 flex-col gap-0.5">
        <div className="flex items-center gap-2">
          {forumComment.user ? (
            <span className="font-medium text-link">{forumComment.user.displayName}</span>
          ) : (
            <span className="text-neutral-400">{t('Unknown User')}</span>
          )}

          <span className="text-xs text-neutral-400">
            {t('posted {{when}}', { when: diffForHumans(forumComment.createdAt) })}
          </span>
        </div>

        {forumComment.forumTopic ? (
          <div className="truncate text-xs text-neutral-500 light:text-neutral-600">
            <Trans
              i18nKey="in <1>{{forumTopicTitle}}</1>"
              values={{ forumTopicTitle: forumComment.forumTopic.title }}
              components={{ 1: <span /> }}
            />
          </div>
        ) : null}

        <div className="line-clamp-2 text-xs text-neutral-300 light:text-neutral-700">
          {cleanBody}
        </div>
      </div>
    </div>
  );
};
