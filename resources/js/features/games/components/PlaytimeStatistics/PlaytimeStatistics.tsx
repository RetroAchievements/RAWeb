import * as motion from 'motion/react-m';
import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuAward, LuCheck, LuCircleDot } from 'react-icons/lu';

import { BaseToggleGroup, BaseToggleGroupItem } from '@/common/components/+vendor/BaseToggleGroup';
import { useFormatDate } from '@/common/hooks/useFormatDate';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { PlayMode } from '@/common/models';

import { PlaytimeRow } from './PlaytimeRow';

export const PlaytimeStatistics: FC = () => {
  const {
    backingGame,
    game,
    numBeaten,
    numBeatenSoftcore,
    numCompletions,
    numMasters,
    targetAchievementSetPlayersHardcore,
    targetAchievementSetPlayersTotal,
  } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const { t } = useTranslation();
  const { formatDate } = useFormatDate();

  const [currentMode, setCurrentMode] = useState<PlayMode>('hardcore');
  const [hasUserToggled, setHasUserToggled] = useState(false);

  // These calculations don't account for the edge case of when there are
  // multiple sets selected on one page. Bail.
  if (!game.gameAchievementSets || game.gameAchievementSets.length > 1) {
    return null;
  }

  const achievementSet = game.gameAchievementSets[0].achievementSet;

  // Use target set player counts if available, otherwise fall back to game player counts.
  // The back-end sets target set player counts to the UI only if the player is viewing
  // a non-core set. Those subset player counts are derived from player_achievement_sets.
  const playersHardcore = targetAchievementSetPlayersHardcore ?? game.playersHardcore!;
  const playersTotal = targetAchievementSetPlayersTotal ?? game.playersTotal!;
  const totalPlayers =
    currentMode === 'hardcore' ? playersHardcore : playersTotal - playersHardcore;

  const handleValueChange = (val?: PlayMode) => {
    if (!val) {
      return;
    }

    setCurrentMode(val);
    setHasUserToggled(true);
  };

  return (
    <div data-testid="playtime-statistics">
      <div className="flex w-full items-center justify-between">
        <h2 className="mb-0 border-0 text-lg font-semibold">{t('Playtime Stats')}</h2>

        <BaseToggleGroup
          type="single"
          className="mb-px gap-px"
          value={currentMode}
          onValueChange={(val: string) => handleValueChange(val as PlayMode | undefined)}
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
        animate={hasUserToggled ? { opacity: [0.7, 1] } : undefined}
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
          <p className="py-1 text-center text-xs text-neutral-500 light:text-neutral-700">
            {t('Achievements available since {{date}}', {
              date: formatDate(achievementSet.achievementsFirstPublishedAt, 'll'),
            })}
          </p>
        ) : null}
      </motion.div>
    </div>
  );
};
