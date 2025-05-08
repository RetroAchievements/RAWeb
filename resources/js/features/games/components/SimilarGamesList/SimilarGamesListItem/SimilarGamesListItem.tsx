import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { FaTrophy } from 'react-icons/fa';
import { route } from 'ziggy-js';

import { GameTitle } from '@/common/components/GameTitle';
import { InertiaLink } from '@/common/components/InertiaLink';
import { SystemChip } from '@/common/components/SystemChip';
import { useCardTooltip } from '@/common/hooks/useCardTooltip';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

interface SimilarGamesListItemProps {
  game: App.Platform.Data.Game;
}

export const SimilarGamesListItem: FC<SimilarGamesListItemProps> = ({ game }) => {
  const { auth } = usePageProps();

  const { t } = useTranslation();

  const { cardTooltipProps } = useCardTooltip({
    dynamicType: 'game',
    dynamicId: game.id,
    dynamicContext: auth?.user.displayName,
  });

  return (
    <div className="flex w-full items-center justify-between gap-2">
      <div className="relative flex items-center gap-x-2">
        {/* Keep the image and game title in a single tooltipped container. Do not tooltip the system name. */}
        <InertiaLink href={route('game2.show', { game: game.id })} {...cardTooltipProps}>
          <img
            src={game.badgeUrl}
            alt={game.title}
            width={36}
            height={36}
            className="h-9 w-9"
            loading="lazy"
            decoding="async"
          />

          <p className="absolute left-7 top-0 mb-0.5 line-clamp-1 pl-4 text-xs font-medium">
            <GameTitle title={game.title} />
          </p>
        </InertiaLink>

        <div>
          {/* Provide invisible space to slide the system underneath. */}
          <p className="invisible mb-0.5 line-clamp-1 max-w-fit text-xs font-medium">
            <GameTitle title={game.title} />
          </p>

          <div className="flex items-center gap-1">
            {game.system ? <SystemChip {...game.system} /> : null}
          </div>
        </div>
      </div>

      <div className="flex flex-col items-end whitespace-nowrap">
        <p
          className={cn(
            'flex items-center gap-1 text-xs',
            game.achievementsPublished === 0
              ? 'text-neutral-600 light:text-neutral-400'
              : 'text-neutral-300 light:text-neutral-700',
          )}
        >
          <FaTrophy /> {game.achievementsPublished}
        </p>

        <p
          className={cn(
            'flex items-center gap-1 text-2xs',
            game.achievementsPublished === 0 ? 'text-neutral-600 light:text-neutral-400' : null,
          )}
        >
          {t('{{val, number}} points', {
            val: game.pointsTotal,
            count: game.pointsTotal,
          })}
        </p>
      </div>
    </div>
  );
};
