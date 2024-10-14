import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';
import { useEffect, useState } from 'react';
import { MdClose } from 'react-icons/md';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useWantToPlayGamesList } from '@/common/hooks/useWantToPlayGamesList';
import { cn } from '@/utils/cn';

import { useDelayedButtonDisable } from './useDelayedButtonDisable';

interface GameListItemDrawerBacklogToggleButtonProps {
  game: App.Platform.Data.Game;
  isInBacklog: boolean;

  onToggle?: (newValue: boolean) => unknown;
}

export const GameListItemDrawerBacklogToggleButton: FC<
  GameListItemDrawerBacklogToggleButtonProps
> = ({ game, isInBacklog, onToggle }) => {
  const { auth } = usePageProps();

  const { t } = useLaravelReactI18n();

  const { addToWantToPlayGamesList, isPending, removeFromWantToPlayGamesList } =
    useWantToPlayGamesList();

  // We want to change the icon instantly for the user, even if the mutation is still running.
  const [isInBacklogOptimistic, setIsInBacklogOptimistic] = useState(isInBacklog);

  const isButtonDisabled = useDelayedButtonDisable(isPending);

  // When the actual `isInBacklog` changes, update the optimistic state accordingly.
  useEffect(() => {
    setIsInBacklogOptimistic(isInBacklog);
  }, [isInBacklog]);

  const handleToggleFromBacklogClick = () => {
    if (!auth?.user && typeof window !== 'undefined') {
      window.location.href = route('login');

      return;
    }

    const newValue = !isInBacklogOptimistic;

    setIsInBacklogOptimistic(newValue);
    onToggle?.(newValue);

    const mutationPromise = isInBacklog
      ? removeFromWantToPlayGamesList(game.id, game.title, { shouldDisableToast: true })
      : addToWantToPlayGamesList(game.id, game.title, { shouldDisableToast: true });

    mutationPromise.catch(() => {
      setIsInBacklogOptimistic(isInBacklog);
      onToggle?.(isInBacklog);
    });
  };

  return (
    <BaseButton
      variant="secondary"
      className={cn(
        'border disabled:!pointer-events-auto disabled:!opacity-100',
        isInBacklogOptimistic ? '!border-red-500 !bg-embed !text-red-500' : 'border-transparent',
      )}
      onClick={handleToggleFromBacklogClick}
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
