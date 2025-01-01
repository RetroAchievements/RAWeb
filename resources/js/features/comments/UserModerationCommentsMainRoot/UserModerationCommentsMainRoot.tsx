import { type FC, memo } from 'react';
import { useTranslation } from 'react-i18next';

import { CommentList } from '@/common/components/CommentList/CommentList';
import { FullPaginator } from '@/common/components/FullPaginator';
import { UserBreadcrumbs } from '@/common/components/UserBreadcrumbs';
import { UserHeading } from '@/common/components/UserHeading';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useCommentPagination } from '../hooks/useCommentPagination';

export const UserModerationCommentsMainRoot: FC = memo(() => {
  const { canComment, paginatedComments, targetUser } =
    usePageProps<App.Community.Data.UserModerationCommentsPageProps>();

  const { t } = useTranslation();

  const { handleCommentDeleteSuccess, handleCommentSubmitSuccess, handlePageSelectValueChange } =
    useCommentPagination({
      paginatedComments,
      entityId: targetUser.id!,
      entityType: 'User', // required to build the /user/{game}/moderation-comments routes correctly
      routeName: 'user.moderation-comment.index',
      displayName: targetUser.displayName,
    });

  return (
    <div>
      <UserBreadcrumbs user={targetUser} t_currentPageLabel={t('Moderation Comments')} />
      <UserHeading user={targetUser} wrapperClassName="!mb-1">
        {t('Moderation Comments')}
      </UserHeading>

      <div className="mb-3 flex w-full justify-between">
        <FullPaginator
          onPageSelectValueChange={handlePageSelectValueChange}
          paginatedData={paginatedComments}
        />
      </div>

      <CommentList
        canComment={canComment}
        comments={paginatedComments.items}
        commentableId={targetUser.id!}
        commentableType="UserModeration"
        onDeleteSuccess={handleCommentDeleteSuccess}
        onSubmitSuccess={handleCommentSubmitSuccess}
        targetUserDisplayName={targetUser.displayName}
      />

      <div className="mt-8 flex justify-center sm:mt-3 sm:justify-start">
        <FullPaginator
          onPageSelectValueChange={handlePageSelectValueChange}
          paginatedData={paginatedComments}
        />
      </div>
    </div>
  );
});
