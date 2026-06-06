import { router } from '@inertiajs/react';
import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { CommentList } from '@/common/components/CommentList';
import { InertiaLink } from '@/common/components/InertiaLink';
import { SubscribeToggleButton } from '@/common/components/SubscribeToggleButton';
import { usePageProps } from '@/common/hooks/usePageProps';

export const AchievementCommentList: FC = () => {
  const {
    achievement,
    can,
    eventAchievement,
    isSubscribedToComments,
    numComments,
    recentVisibleComments,
  } = usePageProps<App.Platform.Data.AchievementShowPageProps>();
  const { t } = useTranslation();

  // For event achievements, comments come from the source achievement.
  const commentableId = eventAchievement?.sourceAchievement?.id ?? achievement.id;

  const reloadComments = () => {
    router.reload({ only: ['recentVisibleComments', 'numComments'] });
  };

  return (
    <div id="comments" className="flex flex-col gap-2">
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
                      href={route('achievement.comment.index', { achievement: commentableId })}
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
          subjectId={commentableId}
          subjectType="Achievement"
        />
      </div>

      <CommentList
        comments={recentVisibleComments}
        canComment={!!can.createAchievementComments}
        commentableId={commentableId}
        commentableType="achievement.comment"
        onDeleteSuccess={reloadComments}
        onSubmitSuccess={reloadComments}
      />
    </div>
  );
};
