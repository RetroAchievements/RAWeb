import { useQueryClient } from '@tanstack/react-query';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { useAddToGameListMutation } from '@/common/hooks/useAddToGameListMutation';
import { useRemoveFromGameListMutation } from '@/common/hooks/useRemoveFromGameListMutation';

export function useWantToPlayGamesList() {
  const queryClient = useQueryClient();

  const addToBacklogMutation = useAddToGameListMutation();

  const removeFromBacklogMutation = useRemoveFromGameListMutation();

  const isPending = addToBacklogMutation.isPending || removeFromBacklogMutation.isPending;

  const addToWantToPlayGamesList = (
    gameId: number,
    gameTitle: string,
    options?: Partial<{ isUndo: boolean }>,
  ) => {
    const mutationPromise = addToBacklogMutation.mutateAsync(gameId);

    toastMessage.promise(mutationPromise, {
      loading: options?.isUndo ? 'Restoring...' : 'Adding...',
      success: () => {
        // Trigger a refetch of the current table page data and bust the entire cache.
        queryClient.invalidateQueries({ queryKey: ['data'] });

        return `${options?.isUndo ? 'Restored' : 'Added'} ${gameTitle}!`;
      },
      error: 'Something went wrong.',
    });

    return mutationPromise;
  };

  const removeFromWantToPlayGamesList = (gameId: number, gameTitle: string) => {
    const mutationPromise = removeFromBacklogMutation.mutateAsync(gameId);

    toastMessage.promise(mutationPromise, {
      action: {
        label: 'Undo',
        onClick: () => addToWantToPlayGamesList(gameId, gameTitle, { isUndo: true }),
      },
      loading: 'Removing...',
      success: () => {
        // Trigger a refetch of the current table page data and bust the entire cache.
        queryClient.invalidateQueries({ queryKey: ['data'] });

        return `Removed ${gameTitle}!`;
      },
      error: 'Something went wrong.',
    });

    return mutationPromise;
  };

  return { addToWantToPlayGamesList, isPending, removeFromWantToPlayGamesList };
}
