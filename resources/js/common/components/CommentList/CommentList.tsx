import { type FC } from 'react';
import { useTranslation } from 'react-i18next';

import type { ArticleType } from '@/common/utils/generatedAppConstants';

import { usePageProps } from '../../hooks/usePageProps';
import { CommentInput } from './CommentInput';
import { CommentListProvider } from './CommentListContext';
import { CommentListItem } from './CommentListItem';
import { MutedMessage } from './MutedMessage';
import { SignInMessage } from './SignInMessage';

interface CommentListProps {
  /** Can the currently-authenticated user write a comment on this wall? */
  canComment: boolean;
  commentableId: number;
  commentableType: keyof typeof ArticleType;
  comments: App.Community.Data.Comment[];

  onDeleteSuccess?: () => void;
  onSubmitSuccess?: () => void;
  targetUserDisplayName?: string;
}

export const CommentList: FC<CommentListProps> = ({
  canComment,
  commentableId,
  commentableType,
  comments,
  onDeleteSuccess,
  onSubmitSuccess,
  targetUserDisplayName,
}) => {
  const { auth } = usePageProps();

  const { t } = useTranslation();

  return (
    <CommentListProvider
      canComment={canComment}
      commentableId={commentableId}
      commentableType={commentableType}
      onDeleteSuccess={onDeleteSuccess}
      onSubmitSuccess={onSubmitSuccess}
      targetUserDisplayName={targetUserDisplayName}
    >
      <div>
        {comments.length ? (
          <ul className="highlighted-list flex flex-col">
            {comments.map((comment) => (
              <CommentListItem key={comment.id} {...comment} />
            ))}
          </ul>
        ) : (
          <p className="mb-4 italic">{t('No comments yet.')}</p>
        )}

        {auth?.user.isMuted && auth.user.mutedUntil ? (
          <MutedMessage mutedUntil={auth.user.mutedUntil} />
        ) : (
          <CommentInput />
        )}

        {!auth?.user ? <SignInMessage /> : null}
      </div>
    </CommentListProvider>
  );
};
