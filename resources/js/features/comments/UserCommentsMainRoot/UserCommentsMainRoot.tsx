import { type FC, memo } from 'react';
import { useTranslation } from 'react-i18next';

import { CommentList } from '@/common/components/CommentList';
import { FullPaginator } from '@/common/components/FullPaginator';
import { SubscribeToggleButton } from '@/common/components/SubscribeToggleButton';
import { UserBreadcrumbs } from '@/common/components/UserBreadcrumbs';
import { UserHeading } from '@/common/components/UserHeading';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useCommentPagination } from '../hooks/useCommentPagination';

export const UserCommentsMainRoot: FC = memo(() => {
  const { auth, canComment, paginatedComments, isSubscribed, targetUser } =
    usePageProps<App.Community.Data.UserCommentsPageProps>();

  const { t } = useTranslation();

  const { handleCommentDeleteSuccess, handleCommentSubmitSuccess, handlePageSelectValueChange } =
    useCommentPagination({
      paginatedComments,
      entityId: targetUser.id!,
      entityType: 'User',
      routeName: 'user.comment.index',
      displayName: targetUser.displayName,
    });

  return (
    <div>
      <UserBreadcrumbs user={targetUser} t_currentPageLabel={t('Comments')} />
      <UserHeading user={targetUser} wrapperClassName="!mb-1">
        {t('Comments')}
      </UserHeading>

      <div className="mb-3 flex w-full justify-between">
        <FullPaginator
          onPageSelectValueChange={handlePageSelectValueChange}
          paginatedData={paginatedComments}
        />

        {auth ? (
          <SubscribeToggleButton
            subjectId={targetUser.id!}
            subjectType="UserWall"
            hasExistingSubscription={isSubscribed}
          />
        ) : null}
      </div>

      <CommentList
        canComment={canComment}
        comments={paginatedComments.items}
        commentableId={targetUser.id!}
        commentableType="User"
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
