import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';
import { MdClose } from 'react-icons/md';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { cn } from '@/utils/cn';

import { useDelayedButtonDisable } from './useDelayedButtonDisable';
import { useGameBacklogState } from './useGameBacklogState';

interface GameListItemDrawerBacklogToggleButtonProps {
  game: App.Platform.Data.Game;
  isInBacklog: boolean;
}

export const GameListItemDrawerBacklogToggleButton: FC<
  GameListItemDrawerBacklogToggleButtonProps
> = ({ game, isInBacklog }) => {
  const { t } = useLaravelReactI18n();

  const {
    isPending,
    toggleBacklog,
    isInBacklogMaybeOptimistic: isInBacklogOptimistic,
  } = useGameBacklogState({
    game: game,
    isInitiallyInBacklog: isInBacklog,
    shouldShowToasts: false,
  });

  const isButtonDisabled = useDelayedButtonDisable(isPending);

  return (
    <BaseButton
      variant="secondary"
      className={cn(
        'border disabled:!pointer-events-auto disabled:!opacity-100',
        isInBacklogOptimistic ? '!border-red-500 !bg-embed !text-red-500' : 'border-transparent',
      )}
      onClick={toggleBacklog}
      disabled={isPending || isButtonDisabled}
    >
      <MdClose
        className={cn('mr-1 h-4 w-4 transition-all', isInBacklogOptimistic ? '' : 'rotate-45')}
      />

      {t('Want to Play')}

      <span className="sr-only">
        {isInBacklogOptimistic
          ? t('Remove from Want to Play Games')
          : t('Add to Want to Play Games')}
      </span>
    </BaseButton>
  );
};
