import { type FC, memo } from 'react';
import { useTranslation } from 'react-i18next';

import { CommentList } from '@/common/components/CommentList/CommentList';
import { EventBreadcrumbs } from '@/common/components/EventBreadcrumbs';
import { FullPaginator } from '@/common/components/FullPaginator';
import { GameAvatar } from '@/common/components/GameAvatar';
import { SubscribeToggleButton } from '@/common/components/SubscribeToggleButton';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useCommentPagination } from '../hooks/useCommentPagination';

export const EventCommentsMainRoot: FC = memo(() => {
  const { auth, canComment, event, isSubscribed, paginatedComments } =
    usePageProps<App.Community.Data.EventCommentsPageProps>();
  const { t } = useTranslation();

  const { handleCommentDeleteSuccess, handleCommentSubmitSuccess, handlePageSelectValueChange } =
    useCommentPagination({
      paginatedComments,
      entityId: event.id,
      commentableType: 'event.comment',
      routeName: 'event.comment.index',
    });

  return (
    <div>
      <EventBreadcrumbs event={event} t_currentPageLabel={t('Comments')} />

      <div className="mb-1 flex w-full gap-x-3">
        <div className="mb-2 inline self-end">
          <GameAvatar {...event.legacyGame!} showLabel={false} size={48} />
        </div>

        <h1 className="text-h3 w-full self-end sm:mt-2.5 sm:!text-[2.0em]">{t('Comments')}</h1>
      </div>

      <div className="mb-3 flex w-full justify-between">
        <FullPaginator
          onPageSelectValueChange={handlePageSelectValueChange}
          paginatedData={paginatedComments}
        />

        {auth ? (
          <SubscribeToggleButton
            subjectId={event.id}
            subjectType="EventWall"
            hasExistingSubscription={isSubscribed}
          />
        ) : null}
      </div>

      <CommentList
        canComment={canComment}
        comments={paginatedComments.items}
        commentableId={event.id}
        commentableType="event.comment"
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
