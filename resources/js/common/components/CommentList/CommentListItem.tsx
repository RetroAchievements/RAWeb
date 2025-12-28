import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';

import { BaseTooltip, BaseTooltipContent, BaseTooltipTrigger } from '../+vendor/BaseTooltip';
import { DiffTimestamp } from '../DiffTimestamp';
import { FormatNewlines } from '../FormatNewlines';
import { UserAvatar } from '../UserAvatar';
import { useCommentListContext } from './CommentListContext';
import { DeleteCommentButton } from './DeleteCommentButton';
import { ReportCommentButton } from './ReportCommentButton';

type CommentListItemProps = App.Community.Data.Comment;

export const CommentListItem: FC<CommentListItemProps> = ({ ...comment }) => {
  const { auth, ziggy } = usePageProps();
  const { t } = useTranslation();

  const { onDeleteSuccess } = useCommentListContext();

  // These comment types don't support the report button.
  const excludedCommentableTypes: App.Community.Enums.CommentableType[] = [
    'user-moderation.comment',
    'game-hash.comment',
    'achievement-set-claim.comment',
    'game-modification.comment',
  ];

  const canShowReportButton =
    comment.canReport &&
    !comment.isAutomated &&
    !excludedCommentableTypes.includes(comment.commentableType);

  return (
    <li
      id={`comment_${comment.id}`}
      className="group flex w-full scroll-mt-20 items-start gap-4 p-2 target:outline target:outline-2 target:outline-text"
    >
      <div className="mt-1">
        {comment.isAutomated ? (
          <div className="size-8" />
        ) : (
          <UserAvatar {...comment.user} showLabel={false} />
        )}
      </div>

      <div className="w-full">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            {comment.isAutomated ? null : <UserAvatar {...comment.user} showImage={false} />}

            <span className="smalldate">
              <DiffTimestamp
                asAbsoluteDate={auth?.user.preferences.prefersAbsoluteDates ?? false}
                at={comment.createdAt}
              />
            </span>

            {/* Desktop report button */}
            {canShowReportButton && ziggy.device !== 'mobile' ? (
              <BaseTooltip>
                <BaseTooltipTrigger>
                  <ReportCommentButton
                    comment={comment}
                    className="hidden opacity-0 group-hover:opacity-100 sm:block"
                  />
                </BaseTooltipTrigger>

                <BaseTooltipContent>{t('Report')}</BaseTooltipContent>
              </BaseTooltip>
            ) : null}
          </div>

          {/* Delete button & mobile actions */}
          {comment.canDelete || (canShowReportButton && ziggy.device === 'mobile') ? (
            <div className="flex items-center gap-2">
              {canShowReportButton && ziggy.device === 'mobile' ? (
                <ReportCommentButton comment={comment} className="sm:hidden" />
              ) : null}

              {comment.canDelete ? (
                <DeleteCommentButton {...comment} onDeleteSuccess={onDeleteSuccess} />
              ) : null}
            </div>
          ) : null}
        </div>

        {/* Prevent long-running lines from breaking the page layout. */}
        <p
          style={{ wordBreak: 'break-word' }}
          className={comment.isAutomated ? 'mt-1 text-xs text-neutral-500' : ''}
        >
          <FormatNewlines>{comment.payload}</FormatNewlines>
        </p>
      </div>
    </li>
  );
};
