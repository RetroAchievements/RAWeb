import { useCallback, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { useAddOrRemoveFromUserGameList } from '@/common/hooks/useAddOrRemoveFromUserGameList';
import { usePageProps } from '@/common/hooks/usePageProps';

interface UseGameBacklogStateProps {
  game: App.Platform.Data.Game;
  isInitiallyInBacklog: boolean;

  shouldShowToasts?: boolean;
  shouldUpdateOptimistically?: boolean;
  userGameListType?: App.Community.Enums.UserGameListType;
}

export function useGameBacklogState({
  game,
  isInitiallyInBacklog,
  shouldUpdateOptimistically = true,
  userGameListType = 'play',
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

  const { addToGameList, removeFromGameList, isPending } = useAddOrRemoveFromUserGameList();

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

      const mutationOptions: Parameters<typeof removeFromGameList>[2] = {
        userGameListType,
        shouldEnableToast: shouldShowToasts,
        shouldInvalidateCachedQueries: !shouldUpdateOptimistically,
      };

      if (shouldShowToasts) {
        const gameTitle = game.title;
        const addMessage =
          userGameListType === 'play'
            ? t('Added {{gameTitle}} to playlist!', { gameTitle })
            : t('Added {{gameTitle}}!', { gameTitle });
        const removeMessage =
          userGameListType === 'play'
            ? t('Removed {{gameTitle}} from playlist!', { gameTitle })
            : t('Removed {{gameTitle}}!', { gameTitle });

        // Add the success message when toasts are enabled.
        mutationOptions.t_successMessage = newBacklogState ? addMessage : removeMessage;

        // Add the undo callback only when removing from backlog and toasts are enabled.
        if (!newBacklogState) {
          mutationOptions.onUndo = () => setIsInBacklogMaybeOptimistic(true);
        }
      }

      try {
        if (newBacklogState) {
          await addToGameList(game.id, game.title, mutationOptions);
        } else {
          await removeFromGameList(game.id, game.title, mutationOptions);
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
      addToGameList,
      auth?.user,
      game.id,
      game.title,
      isInBacklogMaybeOptimistic,
      removeFromGameList,
      shouldUpdateOptimistically,
      t,
      userGameListType,
    ],
  );

  return { isInBacklogMaybeOptimistic, toggleBacklog, isPending };
}
