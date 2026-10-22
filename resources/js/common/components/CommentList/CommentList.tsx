import dayjs from 'dayjs';
import { type FC, Fragment, type ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

import { type CommentListInterleavedItem } from '@/common/models';

import { usePageProps } from '../../hooks/usePageProps';
import { SignInMessage } from '../SignInMessage';
import { CommentInput } from './CommentInput';
import { CommentListProvider } from './CommentListContext';
import { CommentListItem } from './CommentListItem';
import { MutedMessage } from './MutedMessage';

interface CommentListProps {
  /** Can the currently-authenticated user write a comment on this wall? */
  canComment: boolean;
  commentableId: number;
  commentableType: App.Community.Enums.CommentableType;
  comments: App.Community.Data.Comment[];

  footerSlot?: (comment: App.Community.Data.Comment) => ReactNode;
  hasEmptyState?: boolean;
  interleavedItems?: CommentListInterleavedItem[];
  onDeleteSuccess?: () => void;
  onSubmitSuccess?: () => void;
  targetUserDisplayName?: string;
}

export const CommentList: FC<CommentListProps> = ({
  canComment,
  commentableId,
  commentableType,
  comments,
  interleavedItems,
  onDeleteSuccess,
  onSubmitSuccess,
  footerSlot,
  targetUserDisplayName,
  hasEmptyState = true,
}) => {
  const { auth } = usePageProps();
  const { t } = useTranslation();

  const mappedCommentItems: CommentListInterleavedItem[] = comments.map((comment) => ({
    createdAt: comment.createdAt,
    key: `comment-${comment.id}`,
    node: <CommentListItem {...comment} footer={footerSlot?.(comment)} />,
  }));

  // The sort is stable, so a comment keeps its place ahead of a row with the same timestamp.
  const listItems = interleavedItems?.length
    ? [...mappedCommentItems, ...interleavedItems].sort((a, b) =>
        dayjs(a.createdAt).diff(dayjs(b.createdAt)),
      )
    : mappedCommentItems;

  return (
    // TODO atoms would probably be better here
    <CommentListProvider
      canComment={canComment}
      commentableId={commentableId}
      commentableType={commentableType}
      onDeleteSuccess={onDeleteSuccess}
      onSubmitSuccess={onSubmitSuccess}
      targetUserDisplayName={targetUserDisplayName}
    >
      <div>
        {listItems.length ? (
          <ul className="highlighted-list flex flex-col">
            {listItems.map((item) => (
              <Fragment key={item.key}>{item.node}</Fragment>
            ))}
          </ul>
        ) : null}

        {!listItems.length && hasEmptyState ? (
          <p className="mb-4 italic">{t('No comments yet.')}</p>
        ) : null}

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
