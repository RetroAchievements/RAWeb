import type { FC } from 'react';

import { BaseChip } from '@/common/components/+vendor/BaseChip';
import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { gameListFieldIconMap } from '@/features/game-list/utils/gameListFieldIconMap';

interface AchievementsAndPointsChipProps {
  game: App.Platform.Data.Game;
}

export const AchievementsAndPointsChip: FC<AchievementsAndPointsChipProps> = ({ game }) => {
  const { formatNumber } = useFormatNumber();

  return (
    <BaseChip className="gap-1 text-neutral-300 light:text-neutral-700">
      <span className="flex items-center gap-1">
        <gameListFieldIconMap.achievementsPublished className="size-3" />
        {formatNumber(game.achievementsPublished ?? 0)}
      </span>

      <span className="text-neutral-600 light:text-neutral-500">{'·'}</span>

      <span className="flex items-center gap-1">
        <gameListFieldIconMap.pointsTotal className="size-3" />
        {formatNumber(game.pointsTotal ?? 0)}
      </span>
    </BaseChip>
  );
};
