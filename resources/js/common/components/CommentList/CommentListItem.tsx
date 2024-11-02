import type { FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { DiffTimestamp } from '../DiffTimestamp';
import { FormatNewlines } from '../FormatNewlines';
import { UserAvatar } from '../UserAvatar';
import { useCommentListContext } from './CommentListContext';
import { DeleteCommentButton } from './DeleteCommentButton';

type CommentListItemProps = App.Community.Data.Comment;

export const CommentListItem: FC<CommentListItemProps> = ({ ...comment }) => {
  const { auth } = usePageProps();

  const { onDeleteSuccess } = useCommentListContext();

  return (
    <li className="flex w-full items-start gap-4 p-2">
      <div className="mt-1">
        <UserAvatar {...comment.user} showLabel={false} />
      </div>

      <div className="w-full">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <UserAvatar {...comment.user} showImage={false} />

            <span className="smalldate">
              <DiffTimestamp
                asAbsoluteDate={auth?.user.preferences.prefersAbsoluteDates ?? false}
                at={comment.createdAt}
              />
            </span>
          </div>

          {comment.canDelete ? (
            <DeleteCommentButton {...comment} onDeleteSuccess={onDeleteSuccess} />
          ) : null}
        </div>

        {/* Prevent long-running lines from breaking the page layout. */}
        <p style={{ wordBreak: 'break-word' }}>
          <FormatNewlines>{comment.payload}</FormatNewlines>
        </p>
      </div>
    </li>
  );
};
