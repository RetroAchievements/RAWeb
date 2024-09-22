import dayjs from 'dayjs';
import localizedFormat from 'dayjs/plugin/localizedFormat';
import utc from 'dayjs/plugin/utc';
import type { FC } from 'react';

import { AwardType } from '@/common/utils/generatedAppConstants';
import { getIsEventGame } from '@/common/utils/getIsEventGame';
import { formatNumber } from '@/common/utils/l10n/formatNumber';
import { cn } from '@/utils/cn';

import { BaseProgress } from '../+vendor/BaseProgress';
import { BaseTooltip, BaseTooltipContent, BaseTooltipTrigger } from '../+vendor/BaseTooltip';
import { PlayerBadgeIndicator } from '../PlayerBadgeIndicator/PlayerBadgeIndicator';
import { PlayerBadgeLabel } from '../PlayerBadgeLabel';

dayjs.extend(utc);
dayjs.extend(localizedFormat);

interface PlayerGameProgressBarProps {
  game: App.Platform.Data.Game;
  playerGame: App.Platform.Data.PlayerGame | null;
}

export const PlayerGameProgressBar: FC<PlayerGameProgressBarProps> = ({ game, playerGame }) => {
  const achievementsPublished = game?.achievementsPublished ?? 0;
  const pointsTotal = game.pointsTotal ?? 0;
  const achievementsUnlocked = playerGame?.achievementsUnlocked ?? 0;
  const achievementsUnlockedSoftcore = playerGame?.achievementsUnlockedSoftcore ?? 0;
  const achievementsUnlockedHardcore = playerGame?.achievementsUnlockedHardcore ?? 0;
  const pointsHardcore = playerGame?.pointsHardcore ?? 0;
  const points = (playerGame?.points ?? 0) - pointsHardcore;

  const highestAward = playerGame?.highestAward;

  /**
   * If the user still has achievements to unlock on the game, it's fine to
   * show them their achievements and points breakdown in the tooltip.
   * Otherwise, if there's nothing left for them to unlock, showing those
   * details is largely redundant.
   */
  const canShowDetailedProgress = achievementsUnlocked !== achievementsPublished;

  const isEventGame = getIsEventGame(game);

  if (!achievementsPublished) {
    return null;
  }

  return (
    <BaseTooltip open={achievementsUnlocked === 0 ? false : undefined}>
      <BaseTooltipTrigger
        className={cn(
          'group min-w-[120px] max-w-[120px]',
          achievementsUnlocked === 0 ? '!cursor-auto' : '',
          !highestAward ? 'py-2' : '', // increase the hover surface area
        )}
      >
        <div className="flex max-w-[120px] flex-col gap-0.5">
          <BaseProgress
            max={achievementsPublished}
            segments={[
              {
                value: achievementsUnlockedHardcore,
                className: 'bg-gradient-to-r from-amber-500 to-[gold]',
              },
              { value: achievementsUnlockedSoftcore, className: 'bg-neutral-500' },
            ]}
          />

          {highestAward && !isEventGame ? (
            <div className="flex items-center justify-end gap-1">
              <p className="translate-x-4 opacity-0 transition group-hover:translate-x-0 group-hover:opacity-100 group-focus-visible:translate-x-0 group-focus-visible:opacity-100">
                <PlayerBadgeLabel {...highestAward} className="text-2xs tracking-tighter" />
              </p>
              <PlayerBadgeIndicator {...highestAward} className="mt-px" />
            </div>
          ) : null}
        </div>
      </BaseTooltipTrigger>

      <BaseTooltipContent asChild>
        <div className="text-xs">
          {canShowDetailedProgress ? (
            <>
              {achievementsUnlockedHardcore > 0 ? (
                <p>
                  {formatNumber(achievementsUnlockedHardcore)} of{' '}
                  {formatNumber(achievementsPublished)} achievements unlocked
                </p>
              ) : null}

              {achievementsUnlockedSoftcore > 0 ? (
                <p>
                  {formatNumber(achievementsUnlockedSoftcore)} of{' '}
                  {formatNumber(achievementsPublished)} softcore achievements unlocked
                </p>
              ) : null}

              {pointsHardcore > 0 ? (
                <p>
                  {formatNumber(pointsHardcore)} of {formatNumber(pointsTotal)} points earned
                </p>
              ) : null}

              {points > 0 ? (
                <p>
                  {formatNumber(points)} of {formatNumber(pointsTotal)} softcore points earned
                </p>
              ) : null}
            </>
          ) : null}

          {highestAward ? (
            <p>
              {getAwardLabelFromPlayerBadge(highestAward)} on{' '}
              {getEarnDateLabelFromPlayerBadge(highestAward)}
            </p>
          ) : null}
        </div>
      </BaseTooltipContent>
    </BaseTooltip>
  );
};

function getAwardLabelFromPlayerBadge(playerBadge: App.Platform.Data.PlayerBadge): string {
  let awardLabel = 'Finished';

  const { awardType, awardDataExtra } = playerBadge;

  if (awardType === AwardType.Mastery) {
    awardLabel = awardDataExtra ? 'Mastered' : 'Completed';
  } else if (awardType === AwardType.GameBeaten) {
    awardLabel = awardDataExtra ? 'Beaten' : 'Beaten (softcore)';
  }

  return awardLabel;
}

function getEarnDateLabelFromPlayerBadge(playerBadge: App.Platform.Data.PlayerBadge): string {
  return dayjs.utc(playerBadge.awardDate).format('ll');
}
