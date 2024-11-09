import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { MdClose } from 'react-icons/md';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { cn } from '@/utils/cn';

import type { useGameBacklogState } from '../../useGameBacklogState';
import { useDelayedButtonDisable } from './useDelayedButtonDisable';

interface GameListItemDrawerBacklogToggleButtonProps {
  backlogState: ReturnType<typeof useGameBacklogState>;
  onToggle: () => unknown;
}

export const GameListItemDrawerBacklogToggleButton: FC<
  GameListItemDrawerBacklogToggleButtonProps
> = ({ backlogState, onToggle }) => {
  const { t } = useTranslation();

  const isButtonDisabled = useDelayedButtonDisable(backlogState.isPending);

  return (
    <BaseButton
      variant="secondary"
      className={cn(
        'border disabled:!pointer-events-auto disabled:!opacity-100',
        backlogState.isInBacklogMaybeOptimistic
          ? '!border-red-500 !bg-embed !text-red-500'
          : 'border-transparent',
      )}
      onClick={() => onToggle()}
      disabled={backlogState.isPending || isButtonDisabled}
      data-testid="drawer-backlog-toggle"
    >
      <MdClose
        className={cn(
          'mr-1 h-4 w-4 transition-all',
          backlogState.isInBacklogMaybeOptimistic ? '' : 'rotate-45',
        )}
      />

      {t('Want to Play')}

      <span className="sr-only">
        {backlogState.isInBacklogMaybeOptimistic
          ? t('Remove from Want to Play Games')
          : t('Add to Want to Play Games')}
      </span>
    </BaseButton>
  );
};
