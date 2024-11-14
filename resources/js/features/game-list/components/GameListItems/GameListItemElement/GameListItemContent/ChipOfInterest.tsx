import dayjs from 'dayjs';
import localizedFormat from 'dayjs/plugin/localizedFormat';
import utc from 'dayjs/plugin/utc';
import type { FC, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseChip } from '@/common/components/+vendor/BaseChip';
import { PlayerBadgeIndicator } from '@/common/components/PlayerBadgeIndicator';
import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { buildAwardLabelColorClassNames } from '@/common/utils/buildAwardLabelColorClassNames';
import { buildGameRarityLabel } from '@/common/utils/buildGameRarityLabel';
import { formatGameReleasedAt } from '@/common/utils/formatGameReleasedAt';
import { getIsEventGame } from '@/common/utils/getIsEventGame';
import { formatDate } from '@/common/utils/l10n/formatDate';
import { formatPercentage } from '@/common/utils/l10n/formatPercentage';
import { gameListFieldIconMap } from '@/features/game-list/utils/gameListFieldIconMap';
import { cn } from '@/utils/cn';

dayjs.extend(utc);
dayjs.extend(localizedFormat);

interface ChipOfInterestProps {
  game: App.Platform.Data.Game;

  playerGame?: App.Platform.Data.PlayerGame;
  fieldId?: string;
}

export const ChipOfInterest: FC<ChipOfInterestProps> = ({ game, playerGame, fieldId }) => {
  const { t } = useTranslation();

  const { formatNumber } = useFormatNumber();

  let chipContent: ReactNode = null;

  switch (fieldId) {
    case 'achievementsPublished':
      chipContent = (
        <BaseChip className="text-neutral-200">
          <gameListFieldIconMap.achievementsPublished className="size-3" />
          {formatNumber(game.achievementsPublished ?? 0)}
        </BaseChip>
      );
      break;

    case 'pointsTotal':
      chipContent = (
        <BaseChip className="text-neutral-200">
          <gameListFieldIconMap.pointsTotal className="size-3" />
          {formatNumber(game.pointsTotal ?? 0)}
        </BaseChip>
      );
      break;

    case 'retroRatio':
      chipContent = (
        <BaseChip className="text-neutral-200">
          <gameListFieldIconMap.retroRatio className="size-3" />
          {game.pointsTotal
            ? buildGameRarityLabel(game.pointsTotal, game.pointsWeighted)
            : t('none')}
        </BaseChip>
      );
      break;

    case 'lastUpdated':
      chipContent = (
        <BaseChip
          className={cn(
            'tracking-tighter',
            game.lastUpdated ? 'text-neutral-200' : 'text-text-muted',
          )}
        >
          <gameListFieldIconMap.lastUpdated className="size-3" />
          {game.lastUpdated ? formatDate(dayjs.utc(game.lastUpdated), 'll') : t('unknown')}
        </BaseChip>
      );
      break;

    case 'releasedAt':
      chipContent = (
        <BaseChip
          className={cn(
            'tracking-tighter',
            game.releasedAt ? 'text-neutral-200' : 'text-text-muted',
          )}
        >
          <gameListFieldIconMap.releasedAt className="size-3" />
          {game.releasedAt
            ? formatGameReleasedAt(game.releasedAt, game.releasedAtGranularity)
            : t('unknown')}
        </BaseChip>
      );
      break;

    case 'playersTotal':
      chipContent = (
        <BaseChip className="text-neutral-200">
          <gameListFieldIconMap.playersTotal className="size-3" />
          {formatNumber(game.playersTotal ?? 0)}
        </BaseChip>
      );
      break;

    case 'numVisibleLeaderboards':
      chipContent = (
        <BaseChip className="text-neutral-200">
          <gameListFieldIconMap.numVisibleLeaderboards className="size-3" />
          {formatNumber(game.numVisibleLeaderboards ?? 0)}
        </BaseChip>
      );
      break;

    case 'numUnresolvedTickets':
      chipContent = (
        <BaseChip className="text-neutral-200">
          <gameListFieldIconMap.numUnresolvedTickets className="size-3" />
          {formatNumber(game.numUnresolvedTickets ?? 0)}
        </BaseChip>
      );
      break;

    case 'progress':
      if (playerGame?.achievementsUnlocked && game.achievementsPublished) {
        const isComplete = playerGame.achievementsUnlocked === game.achievementsPublished;

        chipContent = (
          <BaseChip
            data-testid="progress-chip"
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

    case 'hasActiveOrInReviewClaims':
      if (game.hasActiveOrInReviewClaims) {
        chipContent = (
          <BaseChip className="text-neutral-200">
            <gameListFieldIconMap.hasActiveOrInReviewClaims className="size-3" />
            {t('Claimed')}
          </BaseChip>
        );
      }
      break;

    default:
      break;
  }

  return <>{chipContent}</>;
};
