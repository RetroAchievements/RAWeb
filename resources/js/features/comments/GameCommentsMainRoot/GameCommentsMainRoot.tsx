import { router } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';

import { CommentList } from '@/common/components/CommentList/CommentList';
import { FullPaginator } from '@/common/components/FullPaginator';
import { SubscribeToggleButton } from '@/common/components/SubscribeToggleButton';
import { usePageProps } from '@/common/hooks/usePageProps';
import { GameBreadcrumbs } from '@/features/games/components/GameBreadcrumbs';
import { GameHeading } from '@/features/games/components/GameHeading';

export const GameCommentsMainRoot: FC = () => {
  const { auth, game, subscription, paginatedComments } =
    usePageProps<App.Community.Data.GameCommentsPageProps>();

  const { t } = useLaravelReactI18n();

  const handleCommentDeleteSuccess = () => {
    // If there are no comments left on the current page and we're not on
    // the 1st page, go back one page.
    router.visit(
      route('game.comment.index', {
        game: game.id,
        _query: { page: getNewLastPageOnItemDelete(paginatedComments) },
      }),
      { preserveScroll: true },
    );
  };

  const handleCommentSubmitSuccess = () => {
    router.visit(
      route('game.comment.index', {
        game: game.id,
        _query: { page: getNewLastPageOnItemAdd(paginatedComments) },
      }),
      { preserveScroll: true },
    );
  };

  const handlePageSelectValueChange = (newPageValue: number) => {
    router.visit(
      route('game.comment.index', {
        game: game.id,
        _query: { page: newPageValue },
      }),
    );
  };

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
            existingSubscription={subscription}
          />
        ) : null}
      </div>

      <CommentList
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

function getNewLastPageOnItemAdd(
  paginatedComments: App.Community.Data.GameCommentsPageProps['paginatedComments'],
): number {
  const { total, perPage } = paginatedComments;

  const newTotal = total + 1;

  return Math.ceil(newTotal / perPage);
}

function getNewLastPageOnItemDelete(
  paginatedComments: App.Community.Data.GameCommentsPageProps['paginatedComments'],
): number {
  const { total, perPage } = paginatedComments;

  const newTotal = total - 1;

  return Math.ceil(newTotal / perPage);
}
