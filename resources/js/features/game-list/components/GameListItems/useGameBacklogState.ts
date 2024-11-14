import { useCallback, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';
import { useWantToPlayGamesList } from '@/common/hooks/useWantToPlayGamesList';

interface UseGameBacklogStateProps {
  game: App.Platform.Data.Game;
  isInitiallyInBacklog: boolean;

  shouldShowToasts?: boolean;
  shouldUpdateOptimistically?: boolean;
}

export function useGameBacklogState({
  game,
  isInitiallyInBacklog,
  shouldUpdateOptimistically = true,
}: UseGameBacklogStateProps) {
  const { auth } = usePageProps();

  const { t } = useTranslation();

  /**
   * Invalidation of infinite queries is _very_ expensive, both for the
   * user (needing to refetch all loaded pages in the infinite scroll), and
   * for us (needing to actually query/load all that data quickly).
   * To avoid this, we actually support this being optimistic state
   * via the `shouldUpdateOptimistically` option.
   */
  const [isInBacklogMaybeOptimistic, setIsInBacklogMaybeOptimistic] =
    useState(isInitiallyInBacklog);

  const { addToWantToPlayGamesList, removeFromWantToPlayGamesList, isPending } =
    useWantToPlayGamesList();

  // Keep state in sync with prop changes.
  useEffect(() => {
    setIsInBacklogMaybeOptimistic(isInitiallyInBacklog);
  }, [isInitiallyInBacklog]);

  const toggleBacklog = useCallback(
    async (options?: { shouldHideToasts: boolean }) => {
      if (!auth?.user && typeof window !== 'undefined') {
        window.location.href = route('login');

        return;
      }

      const shouldShowToasts = options?.shouldHideToasts !== true;
      const newBacklogState = !isInBacklogMaybeOptimistic;

      // Only update state optimistically if configured to do so.
      if (shouldUpdateOptimistically) {
        setIsInBacklogMaybeOptimistic(newBacklogState);
      }

      const mutationOptions: Parameters<typeof removeFromWantToPlayGamesList>[2] = {
        shouldEnableToast: shouldShowToasts,
      };

      if (shouldShowToasts) {
        // Add the success message when toasts are enabled.
        mutationOptions.t_successMessage = newBacklogState
          ? t('Added {{gameTitle}} to playlist!', { gameTitle: game.title })
          : t('Removed {{gameTitle}} from playlist!', { gameTitle: game.title });

        // Add the undo callback only when removing from backlog and toasts are enabled.
        if (!newBacklogState) {
          mutationOptions.onUndo = () => setIsInBacklogMaybeOptimistic(true);
        }
      }

      try {
        if (newBacklogState) {
          await addToWantToPlayGamesList(game.id, game.title, mutationOptions);
        } else {
          await removeFromWantToPlayGamesList(game.id, game.title, mutationOptions);
        }

        // If we aren't updating optimistically, then update after successful mutation.
        if (!shouldUpdateOptimistically) {
          setIsInBacklogMaybeOptimistic(newBacklogState);
        }
      } catch {
        // We only need to revert if we're configured to update optimistically.
        if (shouldUpdateOptimistically) {
          setIsInBacklogMaybeOptimistic(!newBacklogState);
        }
      }
    },
    [
      addToWantToPlayGamesList,
      auth?.user,
      game.id,
      game.title,
      isInBacklogMaybeOptimistic,
      removeFromWantToPlayGamesList,
      shouldUpdateOptimistically,
      t,
    ],
  );

  return { isInBacklogMaybeOptimistic, toggleBacklog, isPending };
}
