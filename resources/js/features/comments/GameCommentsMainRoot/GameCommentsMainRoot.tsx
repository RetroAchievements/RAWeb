import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { CommentList } from '@/common/components/CommentList/CommentList';
import { FullPaginator } from '@/common/components/FullPaginator';
import { SubscribeToggleButton } from '@/common/components/SubscribeToggleButton';
import { usePageProps } from '@/common/hooks/usePageProps';
import { GameBreadcrumbs } from '@/features/games/components/GameBreadcrumbs';
import { GameHeading } from '@/features/games/components/GameHeading';

import { useCommentPagination } from '../hooks/useCommentPagination';

export const GameCommentsMainRoot: FC = () => {
  const { auth, canComment, game, isSubscribed, paginatedComments } =
    usePageProps<App.Community.Data.GameCommentsPageProps>();

  const { t } = useTranslation();

  const { handleCommentDeleteSuccess, handleCommentSubmitSuccess, handlePageSelectValueChange } =
    useCommentPagination({
      paginatedComments,
      entityId: game.id,
      entityType: 'Game',
      routeName: 'game.comment.index',
    });

  return (
    <div>
      <GameBreadcrumbs game={game} system={game.system} t_currentPageLabel={t('Comments')} />
      <GameHeading game={game} wrapperClassName="!mb-1">
        {t('Comments')}
      </GameHeading>

      <div className="mb-3 flex w-full justify-between">
        <FullPaginator
          onPageSelectValueChange={handlePageSelectValueChange}
          paginatedData={paginatedComments}
        />

        {auth ? (
          <SubscribeToggleButton
            subjectId={game.id}
            subjectType="GameWall"
            hasExistingSubscription={isSubscribed}
          />
        ) : null}
      </div>

      <CommentList
        canComment={canComment}
        comments={paginatedComments.items}
        commentableId={game.id}
        commentableType="Game"
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
};
