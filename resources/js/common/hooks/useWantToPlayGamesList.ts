import { useQueryClient } from '@tanstack/react-query';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { useAddToGameListMutation } from '@/common/hooks/useAddToGameListMutation';
import { useRemoveFromGameListMutation } from '@/common/hooks/useRemoveFromGameListMutation';

export function useWantToPlayGamesList() {
  const queryClient = useQueryClient();

  const addToBacklogMutation = useAddToGameListMutation();

  const removeFromBacklogMutation = useRemoveFromGameListMutation();

  const addToWantToPlayGamesList = (
    gameId: number,
    gameTitle: string,
    options?: Partial<{ isUndo: boolean }>,
  ) => {
    toastMessage.promise(addToBacklogMutation.mutateAsync(gameId), {
      loading: options?.isUndo ? 'Restoring...' : 'Adding...',
      success: () => {
        // Trigger a refetch of the current table page data and bust the entire cache.
        queryClient.invalidateQueries({ queryKey: ['data'] });

        return `${options?.isUndo ? 'Restored' : 'Added'} ${gameTitle}!`;
      },
      error: 'Something went wrong.',
    });
  };

  const removeFromWantToPlayGamesList = (gameId: number, gameTitle: string) => {
    toastMessage.promise(removeFromBacklogMutation.mutateAsync(gameId), {
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
  };

  return { addToWantToPlayGamesList, removeFromWantToPlayGamesList };
}
