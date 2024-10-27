import dayjs from 'dayjs';
import localizedFormat from 'dayjs/plugin/localizedFormat';
import utc from 'dayjs/plugin/utc';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC, ReactNode } from 'react';

import { BaseChip } from '@/common/components/+vendor/BaseChip';
import { PlayerBadgeIndicator } from '@/common/components/PlayerBadgeIndicator';
import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { buildAwardLabelColorClassNames } from '@/common/utils/buildAwardLabelColorClassNames';
import { buildGameRarityLabel } from '@/common/utils/buildGameRarityLabel';
import { formatGameReleasedAt } from '@/common/utils/formatGameReleasedAt';
import { getIsEventGame } from '@/common/utils/getIsEventGame';
import { formatDate } from '@/common/utils/l10n/formatDate';
import { formatPercentage } from '@/common/utils/l10n/formatPercentage';
import { cn } from '@/utils/cn';

import { gameListFieldIconMap } from '../../utils/gameListFieldIconMap';

dayjs.extend(utc);
dayjs.extend(localizedFormat);

interface ChipOfInterestProps {
  game: App.Platform.Data.Game;

  playerGame?: App.Platform.Data.PlayerGame;
  fieldId?: string;
}

export const ChipOfInterest: FC<ChipOfInterestProps> = ({ game, playerGame, fieldId }) => {
  const { t } = useLaravelReactI18n();

  const { formatNumber } = useFormatNumber();

  let chipContent: ReactNode = null;

  switch (fieldId) {
    case 'achievementsPublished':
      if (gameListFieldIconMap.achievementsPublished) {
        chipContent = (
          <BaseChip className="text-neutral-200">
            <gameListFieldIconMap.achievementsPublished className="h-3 w-3" />
            {formatNumber(game.achievementsPublished ?? 0)}
          </BaseChip>
        );
      }
      break;

    case 'pointsTotal':
      if (gameListFieldIconMap.pointsTotal) {
        chipContent = (
          <BaseChip className="text-neutral-200">
            <gameListFieldIconMap.pointsTotal className="h-3 w-3" />
            {formatNumber(game.pointsTotal ?? 0)}
          </BaseChip>
        );
      }
      break;

    case 'retroRatio':
      if (gameListFieldIconMap.retroRatio) {
        chipContent = (
          <BaseChip className="text-neutral-200">
            <gameListFieldIconMap.retroRatio className="h-3 w-3" />
            {game.pointsTotal
              ? buildGameRarityLabel(game.pointsTotal, game.pointsWeighted)
              : t('none')}
          </BaseChip>
        );
      }
      break;

    case 'lastUpdated':
      if (gameListFieldIconMap.lastUpdated) {
        chipContent = (
          <BaseChip
            className={cn(
              'tracking-tighter',
              game.lastUpdated ? 'text-neutral-200' : 'text-text-muted',
            )}
          >
            <gameListFieldIconMap.lastUpdated className="h-3 w-3" />
            {game.lastUpdated ? formatDate(dayjs.utc(game.lastUpdated), 'll') : t('unknown')}
          </BaseChip>
        );
      }
      break;

    case 'releasedAt':
      if (gameListFieldIconMap.releasedAt) {
        chipContent = (
          <BaseChip
            className={cn(
              'tracking-tighter',
              game.releasedAt ? 'text-neutral-200' : 'text-text-muted',
            )}
          >
            <gameListFieldIconMap.releasedAt className="h-3 w-3" />
            {game.releasedAt
              ? formatGameReleasedAt(game.releasedAt, game.releasedAtGranularity)
              : t('unknown')}
          </BaseChip>
        );
      }
      break;

    case 'playersTotal':
      if (gameListFieldIconMap.playersTotal) {
        chipContent = (
          <BaseChip className="text-neutral-200">
            <gameListFieldIconMap.playersTotal className="h-3 w-3" />
            {formatNumber(game.playersTotal ?? 0)}
          </BaseChip>
        );
      }
      break;

    case 'numVisibleLeaderboards':
      if (gameListFieldIconMap.numVisibleLeaderboards) {
        chipContent = (
          <BaseChip className="text-neutral-200">
            <gameListFieldIconMap.numVisibleLeaderboards className="h-3 w-3" />
            {formatNumber(game.numVisibleLeaderboards ?? 0)}
          </BaseChip>
        );
      }
      break;

    case 'numUnresolvedTickets':
      if (gameListFieldIconMap.numUnresolvedTickets) {
        chipContent = (
          <BaseChip className="text-neutral-200">
            <gameListFieldIconMap.numUnresolvedTickets className="h-3 w-3" />
            {formatNumber(game.numUnresolvedTickets ?? 0)}
          </BaseChip>
        );
      }
      break;

    case 'progress':
      if (playerGame?.achievementsUnlocked && game.achievementsPublished) {
        const isComplete = playerGame.achievementsUnlocked === game.achievementsPublished;

        chipContent = (
          <BaseChip
            className={cn(
              'h-[22px]',
              !isComplete ? 'px-2' : null,
              buildAwardLabelColorClassNames(
                playerGame?.highestAward?.awardType,
                playerGame?.highestAward?.awardDataExtra,
              ),
            )}
          >
            {playerGame?.highestAward && !getIsEventGame(game) && (
              <PlayerBadgeIndicator playerBadge={playerGame.highestAward} />
            )}
            {!isComplete &&
              formatPercentage(playerGame.achievementsUnlocked / game.achievementsPublished, {
                maximumFractionDigits: 0,
                minimumFractionDigits: 0,
              })}
          </BaseChip>
        );
      }
      break;

    default:
      break;
  }

  return <>{chipContent}</>;
};
