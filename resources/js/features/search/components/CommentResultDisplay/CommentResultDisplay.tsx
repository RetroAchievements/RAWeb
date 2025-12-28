import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { FaGamepad, FaTicketAlt } from 'react-icons/fa';
import { ImTrophy } from 'react-icons/im';
import { LuMessageSquare, LuTrophy, LuUser } from 'react-icons/lu';

import { useDiffForHumans } from '@/common/utils/l10n/useDiffForHumans';

interface CommentResultDisplayProps {
  comment: App.Community.Data.Comment;
}

export const CommentResultDisplay: FC<CommentResultDisplayProps> = ({ comment }) => {
  const { t } = useTranslation();

  const { diffForHumans } = useDiffForHumans();

  const maxBodyLength = 180;
  const cleanBody =
    comment.payload.length > maxBodyLength
      ? comment.payload.substring(0, maxBodyLength) + '...'
      : comment.payload;

  const getCommentTypeInfo = (): { label: string; icon: React.ReactNode } => {
    switch (comment.commentableType) {
      case 'game.comment':
        return { label: t('Game Comment'), icon: <FaGamepad className="size-3" /> };

      case 'achievement.comment':
        return { label: t('Achievement Comment'), icon: <ImTrophy className="size-3" /> };

      case 'user.comment':
        return { label: t('User Wall Comment'), icon: <LuUser className="size-3" /> };

      case 'leaderboard.comment':
        return { label: t('Leaderboard Comment'), icon: <LuTrophy className="size-3" /> };

      case 'trigger.ticket.comment':
        return { label: t('Ticket Comment'), icon: <FaTicketAlt className="size-3" /> };

      default:
        return { label: t('Comment'), icon: <LuMessageSquare className="size-3" /> };
    }
  };

  const typeInfo = getCommentTypeInfo();

  return (
    <div className="flex w-full items-start gap-3">
      <img
        src={comment.user.avatarUrl}
        alt={comment.user.displayName}
        className="size-10 rounded"
      />

      <div className="flex min-w-0 flex-1 flex-col gap-0.5">
        <div className="-mt-1 flex items-center gap-2">
          <span className="text-text">{comment.user.displayName}</span>

          <span className="text-xs text-neutral-400">
            {t('posted {{when}}', { when: diffForHumans(comment.createdAt) })}
          </span>
        </div>

        <div className="flex items-center gap-1 text-xs text-neutral-500 light:text-neutral-600">
          {typeInfo.icon}
          <span>{typeInfo.label}</span>
        </div>

        <div className="line-clamp-2 text-xs text-neutral-300 light:text-neutral-700">
          {cleanBody}
        </div>
      </div>
    </div>
  );
};
