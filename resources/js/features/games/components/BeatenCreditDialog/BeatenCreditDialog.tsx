import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseDialogContent,
  BaseDialogDescription,
  BaseDialogHeader,
  BaseDialogTitle,
} from '@/common/components/+vendor/BaseDialog';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

import { BeatenCreditAchievementList } from './BeatenCreditAchievementList';
import { BeatenCreditAlert } from './BeatenCreditAlert';
import { JumboTypeMetric } from './JumboTypeMetric';

export const BeatenCreditDialog: FC = () => {
  const { game } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const coreSets = game.gameAchievementSets?.filter((set) => set.type === 'core');

  const progressionAchievements = coreSets?.flatMap((set) =>
    set.achievementSet.achievements.filter((ach) => ach.type === 'progression'),
  );
  const winConditionAchievements = coreSets?.flatMap((set) =>
    set.achievementSet.achievements.filter((ach) => ach.type === 'win_condition'),
  );

  const unlockedProgressionAchievements = progressionAchievements?.filter(
    (ach) => !!ach.unlockedAt,
  );
  const unlockedWinConditionAchievements = winConditionAchievements?.filter(
    (ach) => !!ach.unlockedAt,
  );

  return (
    <BaseDialogContent className="h-full max-w-[52rem] overflow-auto sm:max-h-[60vh]">
      <BaseDialogHeader>
        <BaseDialogTitle>{t('Beaten Game Credit')}</BaseDialogTitle>
        <BaseDialogDescription className="sr-only" />
      </BaseDialogHeader>

      <BeatenCreditAlert
        hasProgressionAchievements={!!progressionAchievements?.length}
        hasWinConditionAchievements={!!winConditionAchievements?.length}
      />

      <div
        className={cn(
          'mb-6 grid gap-4',
          progressionAchievements?.length && winConditionAchievements?.length
            ? 'sm:grid-cols-2'
            : 'grid-cols-1',
        )}
      >
        {progressionAchievements?.length ? (
          <JumboTypeMetric
            type="progression"
            current={unlockedProgressionAchievements!.length}
            total={progressionAchievements.length}
          />
        ) : null}

        {winConditionAchievements?.length ? (
          <JumboTypeMetric
            type="win_condition"
            current={unlockedWinConditionAchievements!.length ? 1 : 0}
            total={1}
          />
        ) : null}
      </div>

      {progressionAchievements?.length ? (
        <BeatenCreditAchievementList type="progression" achievements={progressionAchievements} />
      ) : null}

      {winConditionAchievements?.length ? (
        <BeatenCreditAchievementList type="win_condition" achievements={winConditionAchievements} />
      ) : null}
    </BaseDialogContent>
  );
};
