import { type FC, memo } from 'react';
import { useTranslation } from 'react-i18next';

import { CommentList } from '@/common/components/CommentList';
import { FullPaginator } from '@/common/components/FullPaginator';
import { GameHeading } from '@/common/components/GameHeading';
import { LeaderboardBreadcrumbs } from '@/common/components/LeaderboardBreadcrumbs';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useCommentPagination } from '../hooks/useCommentPagination';

export const LeaderboardCommentsMainRoot: FC = memo(() => {
  const { canComment, leaderboard, paginatedComments } =
    usePageProps<App.Community.Data.LeaderboardCommentsPageProps>();

  const { t } = useTranslation();

  const { handleCommentDeleteSuccess, handleCommentSubmitSuccess, handlePageSelectValueChange } =
    useCommentPagination({
      paginatedComments,
      entityId: leaderboard.id,
      entityType: 'Leaderboard',
      routeName: 'leaderboard.comment.index',
    });

  return (
    <div>
      <LeaderboardBreadcrumbs
        leaderboard={leaderboard}
        game={leaderboard.game}
        system={leaderboard.game?.system}
        t_currentPageLabel={t('Comments')}
      />
      <GameHeading game={leaderboard.game!} wrapperClassName="!mb-1">
        {t('Comments: {{leaderboardTitle}}', { leaderboardTitle: leaderboard.title })}
      </GameHeading>

      <div className="mb-3 flex w-full justify-between">
        <FullPaginator
          onPageSelectValueChange={handlePageSelectValueChange}
          paginatedData={paginatedComments}
        />

        {/* Leaderboards cannot currently be subscribed to. */}
        {/* {auth ? (
          <SubscribeToggleButton
            subjectId={leaderboard.id}
            subjectType="Leaderboard"
            hasExistingSubscription={isSubscribed}
          />
        ) : null} */}
      </div>

      <CommentList
        canComment={canComment}
        comments={paginatedComments.items}
        commentableId={leaderboard.id}
        commentableType="Leaderboard"
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
