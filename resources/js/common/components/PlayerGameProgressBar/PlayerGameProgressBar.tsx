import dayjs from 'dayjs';
import localizedFormat from 'dayjs/plugin/localizedFormat';
import utc from 'dayjs/plugin/utc';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { useFormatPercentage } from '@/common/hooks/useFormatPercentage';
import { useGetAwardLabelFromPlayerBadge } from '@/common/hooks/useGetAwardLabelFromPlayerBadge';
import { buildAwardLabelColorClassNames } from '@/common/utils/buildAwardLabelColorClassNames';
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

  /**
   * Defaults to true. When truthy, links directly to the game page.
   */
  isHyperlink?: boolean;

  isTooltipEnabled?: boolean;

  /**
   * Pass along classnames to the underlying <BaseProgress /> component.
   */
  progressClassName?: string;

  /**
   * Defaults to false. When truthy, show a percentage underneath the progress bar.
   * This is best reserved for mobile-only UI, where tooltips are cumbersome.
   */
  showProgressPercentage?: boolean;

  /**
   * base: The award label is subdued. Useful when many progress bars are in a "stacked" layout.
   * unmuted: The award label is not subdued. Good for when there aren't many bars to display.
   */
  variant?: 'base' | 'unmuted';
}

export const PlayerGameProgressBar: FC<PlayerGameProgressBarProps> = ({
  game,
  playerGame,
  progressClassName,
  isHyperlink = true,
  isTooltipEnabled = true,
  showProgressPercentage = false,
  variant = 'base',
}) => {
  const { t } = useTranslation();

  const { getAwardLabelFromPlayerBadge } = useGetAwardLabelFromPlayerBadge();

  const { formatPercentage } = useFormatPercentage();

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

  const canLinkToGamePage = isHyperlink && achievementsUnlocked;

  return (
    <BaseTooltip open={achievementsUnlocked === 0 || !isTooltipEnabled ? false : undefined}>
      <BaseTooltipTrigger
        className={cn(
          'group min-w-[120px] max-w-[120px]',
          achievementsUnlocked === 0 ? '!cursor-auto' : '',
          !highestAward && isTooltipEnabled ? 'py-2' : '', // increase the hover surface area
        )}
      >
        <Wrapper
          className="flex max-w-[120px] flex-col gap-0.5"
          href={canLinkToGamePage ? route('game.show', { game: game.id }) : undefined}
          aria-label={canLinkToGamePage ? `Navigate to ${game.title}` : undefined}
        >
          <BaseProgress
            className={progressClassName}
            max={achievementsPublished}
            segments={[
              {
                value: achievementsUnlockedHardcore,
                className: 'bg-gradient-to-r from-amber-500 to-[gold]',
              },
              { value: achievementsUnlockedSoftcore, className: 'bg-neutral-500' },
            ]}
          />

          <div className={cn('flex w-full items-center justify-between')}>
            {highestAward && !isEventGame ? (
              <div className={cn('flex items-center gap-1')}>
                <PlayerBadgeIndicator playerBadge={highestAward} className="mt-px" />
                <p>
                  <PlayerBadgeLabel
                    playerBadge={highestAward}
                    className="text-2xs tracking-tighter"
                    variant={variant === 'base' ? 'muted-group' : 'base'}
                  />
                </p>
              </div>
            ) : (
              <>{showProgressPercentage ? <div /> : null}</>
            )}

            {showProgressPercentage ? (
              <p
                className={cn(
                  achievementsUnlocked
                    ? buildAwardLabelColorClassNames(
                        highestAward?.awardType,
                        highestAward?.awardDataExtra,
                      )
                    : 'text-muted italic',
                  'mt-0.5 text-2xs tracking-tighter',
                )}
              >
                {achievementsUnlocked && achievementsPublished
                  ? formatPercentage(achievementsUnlocked / achievementsPublished, {
                      maximumFractionDigits: 0,
                      minimumFractionDigits: 0,
                    })
                  : t('none')}
              </p>
            ) : null}
          </div>
        </Wrapper>
      </BaseTooltipTrigger>

      <BaseTooltipContent asChild>
        <div className="text-xs">
          {canShowDetailedProgress ? (
            <>
              {achievementsUnlockedHardcore > 0 ? (
                <p>
                  {t('{{earned, number}} of {{total, number}} achievements unlocked', {
                    earned: achievementsUnlockedHardcore,
                    total: achievementsPublished,
                  })}
                </p>
              ) : null}

              {achievementsUnlockedSoftcore > 0 ? (
                <p>
                  {t('{{earned, number}} of {{total, number}} softcore achievements unlocked', {
                    earned: achievementsUnlockedSoftcore,
                    total: achievementsPublished,
                  })}
                </p>
              ) : null}

              {pointsHardcore > 0 ? (
                <p>
                  {t('{{earned, number}} of {{total, number}} points earned', {
                    earned: pointsHardcore,
                    total: pointsTotal,
                  })}
                </p>
              ) : null}

              {points > 0 ? (
                <p>
                  {t('{{earned, number}} of {{total, number}} softcore points earned', {
                    earned: points,
                    total: pointsTotal,
                  })}
                </p>
              ) : null}
            </>
          ) : null}

          {highestAward ? (
            <p>
              {t('{{awardLabel}} on {{awardDate}}', {
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
