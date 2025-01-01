import { type FC, memo } from 'react';
import { useTranslation } from 'react-i18next';

import { AchievementBreadcrumbs } from '@/common/components/AchievementBreadcrumbs';
import { AchievementHeading } from '@/common/components/AchievementHeading';
import { CommentList } from '@/common/components/CommentList/CommentList';
import { FullPaginator } from '@/common/components/FullPaginator';
import { SubscribeToggleButton } from '@/common/components/SubscribeToggleButton';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useCommentPagination } from '../hooks/useCommentPagination';

export const AchievementCommentsMainRoot: FC = memo(() => {
  const { achievement, auth, canComment, isSubscribed, paginatedComments } =
    usePageProps<App.Community.Data.AchievementCommentsPageProps>();

  const { t } = useTranslation();

  const { handleCommentDeleteSuccess, handleCommentSubmitSuccess, handlePageSelectValueChange } =
    useCommentPagination({
      paginatedComments,
      entityId: achievement.id,
      entityType: 'Achievement',
      routeName: 'achievement.comment.index',
    });

  return (
    <div>
      <AchievementBreadcrumbs
        achievement={achievement}
        game={achievement.game}
        system={achievement.game?.system}
        t_currentPageLabel={t('Comments')}
      />
      <AchievementHeading achievement={achievement} wrapperClassName="!mb-1">
        {t('Comments')}
      </AchievementHeading>

      <div className="mb-3 flex w-full justify-between">
        <FullPaginator
          onPageSelectValueChange={handlePageSelectValueChange}
          paginatedData={paginatedComments}
        />

        {auth ? (
          <SubscribeToggleButton
            subjectId={achievement.id}
            subjectType="Achievement"
            hasExistingSubscription={isSubscribed}
          />
        ) : null}
      </div>

      <CommentList
        canComment={canComment}
        comments={paginatedComments.items}
        commentableId={achievement.id}
        commentableType="Achievement"
        onDeleteSuccess={handleCommentDeleteSuccess}
        onSubmitSuccess={handleCommentSubmitSuccess}
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
