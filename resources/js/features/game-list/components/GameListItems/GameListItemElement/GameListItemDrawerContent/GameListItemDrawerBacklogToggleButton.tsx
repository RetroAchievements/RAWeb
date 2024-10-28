import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';
import { MdClose } from 'react-icons/md';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { cn } from '@/utils/cn';

import type { useGameBacklogState } from '../../useGameBacklogState';
import { useDelayedButtonDisable } from './useDelayedButtonDisable';

interface GameListItemDrawerBacklogToggleButtonProps {
  backlogState: ReturnType<typeof useGameBacklogState>;
  game: App.Platform.Data.Game;
}

export const GameListItemDrawerBacklogToggleButton: FC<
  GameListItemDrawerBacklogToggleButtonProps
> = ({ backlogState }) => {
  const { t } = useLaravelReactI18n();

  const isButtonDisabled = useDelayedButtonDisable(backlogState.isPending);

  const { isInBacklogMaybeOptimistic: isInBacklogOptimistic } = backlogState;

  return (
    <BaseButton
      variant="secondary"
      className={cn(
        'border disabled:!pointer-events-auto disabled:!opacity-100',
        isInBacklogOptimistic ? '!border-red-500 !bg-embed !text-red-500' : 'border-transparent',
      )}
      onClick={() => backlogState.toggleBacklog({ shouldHideToasts: true })}
      disabled={backlogState.isPending || isButtonDisabled}
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
