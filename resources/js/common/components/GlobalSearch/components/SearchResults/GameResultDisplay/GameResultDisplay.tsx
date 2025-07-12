import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { ImTrophy } from 'react-icons/im';
import { LuUsers } from 'react-icons/lu';

import { GameTitle } from '@/common/components/GameTitle';
import { formatNumber } from '@/common/utils/l10n/formatNumber';

interface GameResultDisplayProps {
  game: App.Platform.Data.Game;
}

export const GameResultDisplay: FC<GameResultDisplayProps> = ({ game }) => {
  // We don't have access to usePageProps in global search.
  const { i18n } = useTranslation();

  return (
    <div className="flex items-center gap-3">
      <img src={game.badgeUrl} alt={game.title} className="size-10 rounded" />

      <div className="flex flex-col gap-0.5">
        <div className="line-clamp-1 font-medium text-link">
          <GameTitle title={game.title} />
        </div>

        <div className="flex items-center gap-4 text-xs text-neutral-500">
          <div className="flex items-center gap-1 text-neutral-400 light:text-neutral-600">
            <img src={game.system?.iconUrl} alt={game.system?.name} width={16} height={16} />
            <span>{game.system?.nameShort}</span>
          </div>

          <div className="flex items-center gap-1 light:text-neutral-600">
            <ImTrophy className="!size-3" />
            {formatNumber(game.achievementsPublished ?? 0, { locale: i18n.language })}
          </div>

          <div className="flex items-center gap-1 light:text-neutral-600">
            <LuUsers className="!size-3" />
            {formatNumber(game.playersTotal ?? 0, { locale: i18n.language })}
          </div>
        </div>
      </div>
    </div>
  );
};
