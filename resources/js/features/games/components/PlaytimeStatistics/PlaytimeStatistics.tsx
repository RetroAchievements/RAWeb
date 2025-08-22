import * as motion from 'motion/react-m';
import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuAward, LuCheck, LuCircleDot } from 'react-icons/lu';

import { BaseToggleGroup, BaseToggleGroupItem } from '@/common/components/+vendor/BaseToggleGroup';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { PlayMode } from '@/common/models';
import { formatDate } from '@/common/utils/l10n/formatDate';

import { PlaytimeRow } from './PlaytimeRow';

export const PlaytimeStatistics: FC = () => {
  const { backingGame, game, numBeaten, numBeatenSoftcore, numCompletions, numMasters } =
    usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const [currentMode, setCurrentMode] = useState<PlayMode>('hardcore');

  // These calculations don't account for the edge case of when there are
  // multiple sets selected on one page. Bail.
  if (!game.gameAchievementSets || game.gameAchievementSets.length > 1) {
    return null;
  }

  const achievementSet = game.gameAchievementSets[0].achievementSet;

  const playersHardcore = game.playersHardcore!;
  const totalPlayers =
    currentMode === 'hardcore' ? playersHardcore : game.playersTotal! - playersHardcore;

  return (
    <div data-testid="playtime-statistics">
      <div className="flex w-full items-center justify-between">
        <h2 className="mb-0 border-0 text-lg font-semibold">{t('Playtime Stats')}</h2>

        <BaseToggleGroup
          type="single"
          className="mb-px gap-px"
          value={currentMode}
          onValueChange={(val: PlayMode) => setCurrentMode(val)}
        >
          <BaseToggleGroupItem
            size="sm"
            value="softcore"
            aria-label={t('Toggle softcore')}
            className="h-[24px] px-1.5 text-2xs"
          >
            {t('Softcore')}
          </BaseToggleGroupItem>

          <BaseToggleGroupItem
            size="sm"
            value="hardcore"
            aria-label={t('Toggle hardcore')}
            className="h-[24px] px-1.5 text-2xs"
          >
            {t('Hardcore')}
          </BaseToggleGroupItem>
        </BaseToggleGroup>
      </div>

      <motion.div
        className="flex flex-col gap-1 rounded-lg bg-embed p-1 light:border light:border-neutral-200 light:bg-white"
        animate={{ opacity: [0.7, 1] }}
        transition={{ duration: 0.3 }}
        key={currentMode}
      >
        <PlaytimeRow
          headingLabel={t('Unlocked an achievement')}
          Icon={LuCheck}
          iconClassName="text-neutral-500"
          iconContainerClassName="bg-neutral-700/20 light:bg-neutral-700/5"
          rowPlayers={totalPlayers}
        />

        {backingGame.id === game.id ? (
          <PlaytimeRow
            headingLabel={t('Beat the game')}
            Icon={LuCircleDot}
            iconClassName="text-neutral-400 light:text-neutral-500"
            iconContainerClassName="bg-neutral-500/30 light:bg-neutral-500/20"
            rowPlayers={currentMode === 'hardcore' ? numBeaten : numBeatenSoftcore}
            rowSeconds={
              currentMode === 'hardcore' ? game.medianTimeToBeatHardcore : game.medianTimeToBeat
            }
            totalPlayers={totalPlayers}
            totalSamples={currentMode === 'hardcore' ? game.timesBeatenHardcore : game.timesBeaten}
          />
        ) : null}

        <PlaytimeRow
          headingLabel={currentMode === 'hardcore' ? t('Mastered') : t('Completed')}
          Icon={LuAward}
          iconClassName={
            currentMode === 'hardcore'
              ? 'text-amber-400 light:text-amber-500'
              : 'text-neutral-200 light:text-neutral-500'
          }
          iconContainerClassName={
            currentMode === 'hardcore' ? 'bg-amber-500/20' : 'bg-neutral-50/30 light:bg-neutral-300'
          }
          rowPlayers={currentMode === 'hardcore' ? numMasters : numCompletions}
          rowSeconds={
            currentMode === 'hardcore'
              ? achievementSet.medianTimeToCompleteHardcore
              : achievementSet.medianTimeToComplete
          }
          totalPlayers={totalPlayers}
          totalSamples={
            currentMode === 'hardcore'
              ? achievementSet.timesCompletedHardcore
              : achievementSet.timesCompleted
          }
        />

        {achievementSet.achievementsFirstPublishedAt ? (
          <p className="py-1 text-center text-xs text-neutral-500">
            {t('Achievements available since {{date}}', {
              date: formatDate(achievementSet.achievementsFirstPublishedAt, 'll'),
            })}
          </p>
        ) : null}
      </motion.div>
    </div>
  );
};
