import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCheck, LuPlus } from 'react-icons/lu';

import { useGameBacklogState } from '@/common/hooks/useGameBacklogState';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';

interface WantToPlayToggleProps {
  variant?: 'base' | 'sm';
}

export const WantToPlayToggle: FC<WantToPlayToggleProps> = ({ variant = 'base' }) => {
  const { backingGame, isOnWantToPlayList: isInitiallyOnWantToPlayList } =
    usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const { toggleBacklog: toggleWantToPlay, isInBacklogMaybeOptimistic: isOnWantToPlayList } =
    useGameBacklogState({
      game: backingGame,
      isInitiallyInBacklog: isInitiallyOnWantToPlayList,
      userGameListType: 'play',
    });

  return (
    <button
      onClick={() => toggleWantToPlay()}
      className={cn(
        'group flex items-center gap-1 whitespace-nowrap rounded-full',
        'border border-white/30 bg-black/70 px-2.5 py-1 shadow-md backdrop-blur-sm hover:bg-black/80',
        'light:border-neutral-300 light:bg-white/80 light:backdrop-blur-md light:hover:bg-white/90',
        variant === 'base' ? 'hover:border-link-hover' : null,
      )}
      aria-pressed={isOnWantToPlayList}
    >
      <div className={cn('relative size-3.5', variant === 'base' ? '-mt-0.5' : null)}>
        <LuPlus
          className={cn(
            'absolute inset-0 text-link transition-[transform,opacity] duration-200 group-hover:text-link-hover',
            'light:text-neutral-700',
            isOnWantToPlayList ? 'rotate-45 scale-75 opacity-0' : 'rotate-0 scale-100 opacity-100',
            variant === 'base' ? 'size-4' : 'size-3.5',
          )}
        />
        <LuCheck
          className={cn(
            'absolute inset-0 size-3.5 text-green-400 transition-[transform,opacity] duration-200',
            'light:text-green-700',
            isOnWantToPlayList ? 'rotate-0 scale-100 opacity-100' : '-rotate-45 scale-75 opacity-0',
            variant === 'base' ? 'size-4' : 'size-3.5',
          )}
        />
      </div>

      <span
        className={cn(
          variant === 'base' ? 'text-sm' : 'text-xs',
          'font-medium text-link group-hover:text-link-hover light:text-neutral-700',
        )}
      >
        {t('game_wantToPlayToggle')}
      </span>
    </button>
  );
};
