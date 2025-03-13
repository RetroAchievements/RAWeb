import { useQueryClient } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { useAddToGameListMutation } from '@/common/hooks/mutations/useAddToGameListMutation';
import { useRemoveFromGameListMutation } from '@/common/hooks/mutations/useRemoveFromGameListMutation';
import type { TranslatedString } from '@/types/i18next';

export function useWantToPlayGamesList() {
  const { t } = useTranslation();

  const queryClient = useQueryClient();

  const addToBacklogMutation = useAddToGameListMutation();

  const removeFromBacklogMutation = useRemoveFromGameListMutation();

  const isPending = addToBacklogMutation.isPending || removeFromBacklogMutation.isPending;

  const addToWantToPlayGamesList = (
    gameId: number,
    gameTitle: string,
    options?: Partial<{
      isUndo: boolean;
      shouldEnableToast: boolean;
      shouldInvalidateCachedQueries?: boolean;
      t_successMessage?: TranslatedString;
    }>,
  ) => {
    const mutationPromise = addToBacklogMutation.mutateAsync(gameId);

    if (options?.shouldInvalidateCachedQueries) {
      mutationPromise.then(() => {
        // Trigger a refetch of the current table page data and bust the entire cache.
        queryClient.invalidateQueries({ queryKey: ['data'] });
      });
    }

    if (options?.shouldEnableToast !== false) {
      toastMessage.promise(mutationPromise, {
        loading: options?.isUndo ? t('Restoring...') : t('Adding...'),
        success: () => {
          if (options?.isUndo) {
            return t('Restored {{gameTitle}}!', { gameTitle });
          }

          return options?.t_successMessage ?? t('Added {{gameTitle}}!', { gameTitle });
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
      shouldInvalidateCachedQueries?: boolean;
      t_successMessage?: TranslatedString;
      onUndo?: () => void;
    }>,
  ) => {
    const mutationPromise = removeFromBacklogMutation.mutateAsync(gameId);

    if (options?.shouldInvalidateCachedQueries) {
      mutationPromise.then(() => {
        // Trigger a refetch of the current table page data and bust the entire cache.
        queryClient.invalidateQueries({ queryKey: ['data'] });
      });
    }

    if (options?.shouldEnableToast !== false) {
      toastMessage.promise(mutationPromise, {
        action: {
          label: t('Undo'),
          onClick: () => {
            options?.onUndo?.();

            return addToWantToPlayGamesList(gameId, gameTitle, {
              isUndo: true,
              shouldInvalidateCachedQueries: options?.shouldInvalidateCachedQueries,
            });
          },
        },
        loading: t('Removing...'),
        success: () => {
          return options?.t_successMessage ?? t('Removed {{gameTitle}}!', { gameTitle });
        },
        error: t('Something went wrong.'),
      });
    }

    return mutationPromise;
  };

  return { addToWantToPlayGamesList, isPending, removeFromWantToPlayGamesList };
}
