import { router } from '@inertiajs/react';
import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { CommentList } from '@/common/components/CommentList';
import { InertiaLink } from '@/common/components/InertiaLink';
import { SubscribeToggleButton } from '@/common/components/SubscribeToggleButton';
import { usePageProps } from '@/common/hooks/usePageProps';

export const GameCommentList: FC = () => {
  const { can, game, isSubscribedToComments, numComments, recentVisibleComments } =
    usePageProps<App.Platform.Data.GameShowPageProps>();

  const { t } = useTranslation();

  const reloadComments = () => {
    router.reload({ only: ['recentVisibleComments'] });
  };

  return (
    <div className="flex flex-col gap-2">
      <div className="flex w-full items-end justify-between">
        <div className="flex items-center gap-1">
          <p>
            {numComments > 20
              ? t('Recent comments:', { nsSeparator: null })
              : t('Comments:', { nsSeparator: null })}
          </p>

          <p className="text-2xs text-neutral-500">
            {numComments > 20 ? (
              <Trans
                i18nKey="(<1>all {{numComments, number}}</1>)"
                values={{ numComments }}
                components={{
                  1: (
                    <InertiaLink
                      href={route('game.comment.index', { game: game.id })}
                      prefetch="desktop-hover-only"
                    />
                  ),
                }}
              />
            ) : null}
          </p>
        </div>

        <SubscribeToggleButton
          hasExistingSubscription={isSubscribedToComments}
          subjectId={game.id}
          subjectType="GameWall"
        />
      </div>

      <CommentList
        comments={recentVisibleComments}
        canComment={!!can.createGameComments}
        commentableId={game.id}
        commentableType="Game"
        onDeleteSuccess={reloadComments}
        onSubmitSuccess={reloadComments}
      />
    </div>
  );
};
