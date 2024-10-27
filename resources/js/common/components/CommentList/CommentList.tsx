import { useLaravelReactI18n } from 'laravel-react-i18n';
import { type FC } from 'react';

import type { ArticleType } from '@/common/utils/generatedAppConstants';

import { usePageProps } from '../../hooks/usePageProps';
import { CommentInput } from './CommentInput';
import { CommentListProvider } from './CommentListContext';
import { CommentListItem } from './CommentListItem';
import { MutedMessage } from './MutedMessage';
import { SignInMessage } from './SignInMessage';

interface CommentListProps {
  commentableId: number;
  commentableType: keyof typeof ArticleType;
  comments: App.Community.Data.Comment[];

  onDeleteSuccess?: () => void;
  onSubmitSuccess?: () => void;
}

export const CommentList: FC<CommentListProps> = ({
  commentableId,
  commentableType,
  comments,
  onDeleteSuccess,
  onSubmitSuccess,
}) => {
  const { auth } = usePageProps();

  const { t } = useLaravelReactI18n();

  return (
    <CommentListProvider
      commentableId={commentableId}
      commentableType={commentableType}
      onDeleteSuccess={onDeleteSuccess}
      onSubmitSuccess={onSubmitSuccess}
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
