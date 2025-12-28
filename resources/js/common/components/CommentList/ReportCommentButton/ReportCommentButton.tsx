import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuFlag } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { cn } from '@/common/utils/cn';

import { baseButtonVariants } from '../../+vendor/BaseButton';

interface ReportCommentButtonProps {
  comment: App.Community.Data.Comment;

  className?: string;
}

export const ReportCommentButton: FC<ReportCommentButtonProps> = ({ comment, className }) => {
  const { t } = useTranslation();

  const commentTypeLabel = getCommentTypeLabel(comment.commentableType);

  return (
    <a
      href={route('message-thread.create', {
        to: 'RAdmin',
        subject: `Report: ${commentTypeLabel} by ${comment.user?.displayName}`,
        rType: 'Comment',
        rId: comment.id,
      })}
      className={baseButtonVariants({
        size: 'sm',
        className: cn('max-h-[22px] gap-1 !p-1 !text-2xs', className),
      })}
    >
      <LuFlag aria-label={t('Report')} className="size-3" />
    </a>
  );
};

/**
 * Map CommentableType values to readable labels for report subjects.
 */
function getCommentTypeLabel(commentableType: App.Community.Enums.CommentableType): string {
  const labels: Partial<Record<App.Community.Enums.CommentableType, string>> = {
    'game.comment': 'Game Wall Comment',
    'achievement.comment': 'Achievement Wall Comment',
    'user.comment': 'User Wall Comment',
    'leaderboard.comment': 'Leaderboard Comment',
    'trigger.ticket.comment': 'Ticket Comment',
  };

  return labels[commentableType] ?? 'Wall Comment';
}
