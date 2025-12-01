import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCheck, LuPlus } from 'react-icons/lu';

import type { useGameBacklogState } from '@/common/hooks/useGameBacklogState';
import { cn } from '@/common/utils/cn';

import { useDelayedButtonDisable } from './useDelayedButtonDisable';

interface GameListItemDialogBacklogToggleButtonProps {
  backlogState: ReturnType<typeof useGameBacklogState>;
  onToggle: () => unknown;
}

export const GameListItemDialogBacklogToggleButton: FC<
  GameListItemDialogBacklogToggleButtonProps
> = ({ backlogState, onToggle }) => {
  const { t } = useTranslation();

  const isButtonDisabled = useDelayedButtonDisable(backlogState.isPending);

  return (
    <button
      className={cn(
        'flex items-center gap-1 whitespace-nowrap rounded-full bg-embed',
        'border border-neutral-700 px-2.5 py-1 backdrop-blur-sm transition-all',
        'light:border-link light:bg-white light:backdrop-blur-md',
      )}
      aria-pressed={backlogState.isInBacklogMaybeOptimistic}
      onClick={() => onToggle()}
      disabled={backlogState.isPending || isButtonDisabled}
      data-testid="dialog-backlog-toggle"
    >
      <div className="relative size-4">
        <LuPlus
          className={cn(
            'absolute inset-0 size-4 text-link transition-all duration-200',
            'light:text-neutral-700',
            backlogState.isInBacklogMaybeOptimistic
              ? 'rotate-45 scale-75 opacity-0'
              : 'rotate-0 scale-100 opacity-100',
          )}
        />
        <LuCheck
          className={cn(
            'absolute inset-0 size-4 text-green-400 transition-all duration-200',
            'light:text-green-700',
            backlogState.isInBacklogMaybeOptimistic
              ? 'rotate-0 scale-100 opacity-100'
              : '-rotate-45 scale-75 opacity-0',
          )}
        />
      </div>

      <span className="text-xs font-medium text-link light:text-neutral-700">
        {t('game_wantToPlayToggle')}
      </span>
    </button>
  );
};
