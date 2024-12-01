import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { CommentList } from '@/common/components/CommentList/CommentList';
import { FullPaginator } from '@/common/components/FullPaginator';
import { GameBreadcrumbs } from '@/common/components/GameBreadcrumbs';
import { GameHeading } from '@/common/components/GameHeading';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useCommentPagination } from '../hooks/useCommentPagination';

export const GameClaimsCommentsMainRoot: FC = () => {
  const { canComment, game, paginatedComments } =
    usePageProps<App.Community.Data.GameClaimsCommentsPageProps>();

  const { t } = useTranslation();

  const { handleCommentDeleteSuccess, handleCommentSubmitSuccess, handlePageSelectValueChange } =
    useCommentPagination({
      paginatedComments,
      entityId: game.id,
      entityType: 'Game', // required to build the /game/{game}/claims/comments routes correctly
      routeName: 'game.claims.comment.index',
    });

  return (
    <div>
      <GameBreadcrumbs game={game} system={game.system} t_currentPageLabel={t('Hash Comments')} />
      <GameHeading game={game} wrapperClassName="!mb-1">
        {t('Claim Comments')}
      </GameHeading>

      <div className="mb-3 flex w-full justify-between">
        <FullPaginator
          onPageSelectValueChange={handlePageSelectValueChange}
          paginatedData={paginatedComments}
        />
      </div>

      <CommentList
        canComment={canComment}
        comments={paginatedComments.items}
        commentableId={game.id}
        commentableType="SetClaim"
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
