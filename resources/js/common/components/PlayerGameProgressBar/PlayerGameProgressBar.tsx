import dayjs from 'dayjs';
import localizedFormat from 'dayjs/plugin/localizedFormat';
import utc from 'dayjs/plugin/utc';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';

import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { useGetAwardLabelFromPlayerBadge } from '@/common/hooks/useGetAwardLabelFromPlayerBadge';
import { getIsEventGame } from '@/common/utils/getIsEventGame';
import { cn } from '@/utils/cn';

import { BaseProgress } from '../+vendor/BaseProgress';
import { BaseTooltip, BaseTooltipContent, BaseTooltipTrigger } from '../+vendor/BaseTooltip';
import { PlayerBadgeIndicator } from '../PlayerBadgeIndicator';
import { PlayerBadgeLabel } from '../PlayerBadgeLabel';

dayjs.extend(utc);
dayjs.extend(localizedFormat);

interface PlayerGameProgressBarProps {
  game: App.Platform.Data.Game;
  playerGame: App.Platform.Data.PlayerGame | null;
}

export const PlayerGameProgressBar: FC<PlayerGameProgressBarProps> = ({ game, playerGame }) => {
  const { t } = useLaravelReactI18n();

  const { getAwardLabelFromPlayerBadge } = useGetAwardLabelFromPlayerBadge();

  const { formatNumber } = useFormatNumber();

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

  const Wrapper = achievementsUnlocked ? 'a' : 'div';

  return (
    <BaseTooltip open={achievementsUnlocked === 0 ? false : undefined}>
      <BaseTooltipTrigger
        className={cn(
          'group min-w-[120px] max-w-[120px]',
          achievementsUnlocked === 0 ? '!cursor-auto' : '',
          !highestAward ? 'py-2' : '', // increase the hover surface area
        )}
      >
        <Wrapper
          className="flex max-w-[120px] flex-col gap-0.5"
          href={achievementsUnlocked ? route('game.show', { game: game.id }) : undefined}
          aria-label={achievementsUnlocked ? `Navigate to ${game.title}` : undefined}
        >
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
            <div className="flex items-center gap-1">
              <PlayerBadgeIndicator playerBadge={highestAward} className="mt-px" />
              <p>
                <PlayerBadgeLabel
                  playerBadge={highestAward}
                  className="text-2xs tracking-tighter"
                  variant="muted-group"
                />
              </p>
            </div>
          ) : null}
        </Wrapper>
      </BaseTooltipTrigger>

      <BaseTooltipContent asChild>
        <div className="text-xs">
          {canShowDetailedProgress ? (
            <>
              {achievementsUnlockedHardcore > 0 ? (
                <p>
                  {t(':earned of :total achievements unlocked', {
                    earned: formatNumber(achievementsUnlockedHardcore),
                    total: formatNumber(achievementsPublished),
                  })}
                </p>
              ) : null}

              {achievementsUnlockedSoftcore > 0 ? (
                <p>
                  {t(':earned of :total softcore achievements unlocked', {
                    earned: formatNumber(achievementsUnlockedSoftcore),
                    total: formatNumber(achievementsPublished),
                  })}
                </p>
              ) : null}

              {pointsHardcore > 0 ? (
                <p>
                  {t(':earned of :total points earned', {
                    earned: formatNumber(pointsHardcore),
                    total: formatNumber(pointsTotal),
                  })}
                </p>
              ) : null}

              {points > 0 ? (
                <p>
                  {t(':earned of :total softcore points earned', {
                    earned: formatNumber(points),
                    total: formatNumber(pointsTotal),
                  })}
                </p>
              ) : null}
            </>
          ) : null}

          {highestAward ? (
            <p>
              {t(':awardLabel on :awardDate', {
                awardLabel: getAwardLabelFromPlayerBadge(highestAward),
                awardDate: getEarnDateLabelFromPlayerBadge(highestAward),
              })}
            </p>
          ) : null}
        </div>
      </BaseTooltipContent>
    </BaseTooltip>
  );
};

function getEarnDateLabelFromPlayerBadge(playerBadge: App.Platform.Data.PlayerBadge): string {
  return dayjs.utc(playerBadge.awardDate).format('ll');
}
