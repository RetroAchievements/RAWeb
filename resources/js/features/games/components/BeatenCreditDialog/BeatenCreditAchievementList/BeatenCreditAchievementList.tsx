import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { AchievementAvatar } from '@/common/components/AchievementAvatar';
import { RaProgression } from '@/common/components/RaProgression';
import { RaWinCondition } from '@/common/components/RaWinCondition';
import { cn } from '@/common/utils/cn';

interface BeatenCreditAchievementListProps {
  achievements: App.Platform.Data.Achievement[];
  type: 'progression' | 'win_condition';
}

export const BeatenCreditAchievementList: FC<BeatenCreditAchievementListProps> = ({
  achievements,
  type,
}) => {
  const { t } = useTranslation();

  const Icon = type === 'progression' ? RaProgression : RaWinCondition;

  return (
    <div className="flex flex-col gap-2">
      <div className="flex flex-col sm:flex-row sm:items-center sm:gap-2">
        <div className="flex items-center gap-2">
          <Icon
            className={cn(
              '-mb-0.5 size-5',
              type === 'progression' ? 'text-green-500' : 'text-amber-500',
            )}
          />

          <p className="text-lg font-semibold">
            {type === 'progression' ? t('Progression Achievements') : null}
            {type === 'win_condition' ? t('Win Condition Achievements') : null}
          </p>
        </div>

        <p className="text-2xs text-neutral-400">
          {type === 'progression' ? t('(Need ALL)') : null}
          {type === 'win_condition' ? t('(Need ANY)') : null}
        </p>
      </div>

      <div className="grid gap-2 sm:grid-cols-2">
        {achievements.map((achievement) => (
          <div
            key={`${type}-${achievement.id}`}
            className={getAchievementCardClassName(type, !!achievement.unlockedAt)}
          >
            <AchievementAvatar
              {...achievement}
              showLabel={false}
              hasTooltip={false}
              size={40}
              displayLockedStatus="auto"
            />

            <div className="flex flex-col">
              <a href={route('achievement.show', { achievement: achievement.id })}>
                {achievement.title}
              </a>
              <p className={cn(achievement.unlockedAt ? 'text-text' : 'text-neutral-500')}>
                {achievement.description}
              </p>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

function getAchievementCardClassName(
  type: 'progression' | 'win_condition',
  isUnlocked: boolean,
): string {
  const baseClasses = 'flex items-center gap-4 rounded-lg border p-3';

  if (type === 'progression') {
    return cn(
      baseClasses,
      isUnlocked
        ? 'border-green-700/30 bg-green-900/20'
        : 'border-neutral-700/30 bg-neutral-800/50',
    );
  }

  return cn(
    baseClasses,
    isUnlocked ? 'border-amber-700/30 bg-amber-900/20' : 'border-neutral-700/30 bg-neutral-800/50',
  );
}
