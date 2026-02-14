import { router } from '@inertiajs/react';
import type { FC } from 'react';
import { Trans } from 'react-i18next';
import { route } from 'ziggy-js';

import { CommentList } from '@/common/components/CommentList';
import { InertiaLink } from '@/common/components/InertiaLink';
import { SubscribeToggleButton } from '@/common/components/SubscribeToggleButton';
import { usePageProps } from '@/common/hooks/usePageProps';

export const AchievementCommentList: FC = () => {
  const { achievement, can, isSubscribedToComments, numComments, recentVisibleComments } =
    usePageProps<App.Platform.Data.AchievementShowPageProps>();

  const reloadComments = () => {
    router.reload({ only: ['recentVisibleComments'] });
  };

  return (
    <div id="comments" className="flex flex-col gap-2">
      <div className="flex w-full items-end justify-between">
        <div className="flex items-center gap-1">
          <p className="text-2xs text-neutral-500">
            {numComments > 20 ? (
              <Trans
                i18nKey="(<1>all {{numComments, number}}</1>)"
                values={{ numComments }}
                components={{
                  1: (
                    <InertiaLink
                      href={route('achievement.comment.index', { achievement: achievement.id })}
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
          subjectId={achievement.id}
          subjectType="Achievement"
        />
      </div>

      <CommentList
        comments={recentVisibleComments}
        canComment={!!can.createAchievementComments}
        commentableId={achievement.id}
        commentableType="achievement.comment"
        onDeleteSuccess={reloadComments}
        onSubmitSuccess={reloadComments}
      />
    </div>
  );
};
