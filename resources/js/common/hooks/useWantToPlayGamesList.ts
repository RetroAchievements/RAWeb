import { useQueryClient } from '@tanstack/react-query';
import { useLaravelReactI18n } from 'laravel-react-i18n';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { useAddToGameListMutation } from '@/common/hooks/useAddToGameListMutation';
import { useRemoveFromGameListMutation } from '@/common/hooks/useRemoveFromGameListMutation';

export function useWantToPlayGamesList() {
  const { t } = useLaravelReactI18n();

  const queryClient = useQueryClient();

  const addToBacklogMutation = useAddToGameListMutation();

  const removeFromBacklogMutation = useRemoveFromGameListMutation();

  const isPending = addToBacklogMutation.isPending || removeFromBacklogMutation.isPending;

  const addToWantToPlayGamesList = (
    gameId: number,
    gameTitle: string,
    options?: Partial<{ isUndo: boolean; shouldEnableToast: boolean; t_successMessage?: string }>,
  ) => {
    const mutationPromise = addToBacklogMutation.mutateAsync(gameId);

    mutationPromise.then(() => {
      // Trigger a refetch of the current table page data and bust the entire cache.
      queryClient.invalidateQueries({ queryKey: ['data'] });
    });

    if (options?.shouldEnableToast !== false) {
      toastMessage.promise(mutationPromise, {
        loading: options?.isUndo ? t('Restoring...') : t('Adding...'),
        success: () => {
          if (options?.isUndo) {
            return t('Restored :gameTitle!', { gameTitle });
          }

          return options?.t_successMessage ?? t('Added :gameTitle!', { gameTitle });
        },
        error: t('Something went wrong.'),
      });
    }

    return mutationPromise;
  };

  const removeFromWantToPlayGamesList = (
    gameId: number,
    gameTitle: string,
    options?: Partial<{
      shouldEnableToast: boolean;
      t_successMessage?: string;
      onUndo?: () => void;
    }>,
  ) => {
    const mutationPromise = removeFromBacklogMutation.mutateAsync(gameId);

    mutationPromise.then(() => {
      // Trigger a refetch of the current table page data and bust the entire cache.
      queryClient.invalidateQueries({ queryKey: ['data'] });
    });

    if (options?.shouldEnableToast !== false) {
      toastMessage.promise(mutationPromise, {
        action: {
          label: t('Undo'),
          onClick: () => {
            options?.onUndo?.();

            return addToWantToPlayGamesList(gameId, gameTitle, { isUndo: true });
          },
        },
        loading: t('Removing...'),
        success: () => {
          return options?.t_successMessage ?? t('Removed :gameTitle!', { gameTitle });
        },
        error: t('Something went wrong.'),
      });
    }

    return mutationPromise;
  };

  return { addToWantToPlayGamesList, isPending, removeFromWantToPlayGamesList };
}
