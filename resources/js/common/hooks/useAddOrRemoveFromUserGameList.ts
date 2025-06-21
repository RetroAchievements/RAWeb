import { useQueryClient } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { useAddToGameListMutation } from '@/common/hooks/mutations/useAddToGameListMutation';
import { useRemoveFromGameListMutation } from '@/common/hooks/mutations/useRemoveFromGameListMutation';
import type { TranslatedString } from '@/types/i18next';

export function useAddOrRemoveFromUserGameList() {
  const { t } = useTranslation();

  const queryClient = useQueryClient();
  const addToGameListMutation = useAddToGameListMutation();
  const removeFromGameListMutation = useRemoveFromGameListMutation();

  const isPending = addToGameListMutation.isPending || removeFromGameListMutation.isPending;

  const addToGameList = (
    gameId: number,
    gameTitle: string,
    options?: Partial<{
      isUndo: boolean;
      shouldEnableToast: boolean;
      shouldInvalidateCachedQueries?: boolean;
      t_successMessage?: TranslatedString;
      userGameListType?: App.Community.Enums.UserGameListType;
    }>,
  ) => {
    const mutationPromise = addToGameListMutation.mutateAsync({
      gameId,
      userGameListType: options?.userGameListType ?? 'play',
    });

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

  const removeFromGameList = (
    gameId: number,
    gameTitle: string,
    options?: Partial<{
      shouldEnableToast: boolean;
      onUndo?: () => void;
      shouldInvalidateCachedQueries?: boolean;
      t_successMessage?: TranslatedString;
      userGameListType?: App.Community.Enums.UserGameListType;
    }>,
  ) => {
    const mutationPromise = removeFromGameListMutation.mutateAsync({
      gameId,
      userGameListType: options?.userGameListType ?? 'play',
    });

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

            return addToGameList(gameId, gameTitle, {
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

  return { addToGameList, isPending, removeFromGameList };
}
