import { type FC, memo, useState } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseLabel } from '@/common/components/+vendor/BaseLabel';
import { BaseSwitch } from '@/common/components/+vendor/BaseSwitch';
import { GameHeading } from '@/common/components/GameHeading';
import { UserBreadcrumbs } from '@/common/components/UserBreadcrumbs';
import { usePageProps } from '@/common/hooks/usePageProps';

import { UserGameActivityClientBreakdown } from '../UserGameActivityClientBreakdown';
import { UserGameActivityTimeline } from '../UserGameActivityTimeline';
import { UserGameSummarizedActivity } from '../UserGameSummarizedActivity';

export const UserGameActivityMainRoot: FC = memo(() => {
  const { game, player } = usePageProps<App.Platform.Data.PlayerGameActivityPageProps>();

  const { t } = useTranslation();

  const [isOnlyShowingAchievementSessions, setIsOnlyShowingAchievementSessions] = useState(false);

  return (
    <div>
      <UserBreadcrumbs game={game} user={player} t_currentPageLabel={t('Activity')} />
      <GameHeading game={game} wrapperClassName="!mb-1">
        {t('Game Activity - {{user}}', { user: player.displayName })}
      </GameHeading>

      <div className="flex flex-col gap-5">
        <div className="flex flex-col gap-2">
          <div className="flex flex-col items-start justify-between gap-4 rounded-lg bg-embed p-3 sm:flex-row">
            <UserGameActivityClientBreakdown />

            <div className="flex items-center">
              <BaseLabel
                htmlFor="toggle-productive-sessions"
                className="max-w-[220px] leading-4 text-neutral-300 light:text-neutral-900"
              >
                {t('Hide all player sessions where achievements were not earned')}
              </BaseLabel>
              <BaseSwitch
                id="toggle-productive-sessions"
                checked={isOnlyShowingAchievementSessions}
                onCheckedChange={setIsOnlyShowingAchievementSessions}
              />
            </div>
          </div>

          <UserGameSummarizedActivity />
        </div>

        <UserGameActivityTimeline
          isOnlyShowingAchievementSessions={isOnlyShowingAchievementSessions}
        />
      </div>
    </div>
  );
});
