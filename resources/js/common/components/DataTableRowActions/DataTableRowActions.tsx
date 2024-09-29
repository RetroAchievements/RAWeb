import { useQueryClient } from '@tanstack/react-query';
import type { Row } from '@tanstack/react-table';
import { MdClose } from 'react-icons/md';

import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/utils/cn';

import { useAddToBacklogMutation } from '../../hooks/useAddToBacklogMutation';
import { useRemoveFromBacklogMutation } from '../../hooks/useRemoveFromBacklogMutation';
import { BaseButton } from '../+vendor/BaseButton';
import { toastMessage } from '../+vendor/BaseToaster';
import { BaseTooltip, BaseTooltipContent, BaseTooltipTrigger } from '../+vendor/BaseTooltip';

/**
 * If the table row needs to have more than one action, it should go into a menu.
 * @see https://ui.shadcn.com/examples/tasks
 */

interface DataTableRowActionsProps<TData> {
  row: Row<TData>;
}

export function DataTableRowActions<TData>({ row }: DataTableRowActionsProps<TData>) {
  const { auth } = usePageProps();

  const queryClient = useQueryClient();

  const removeFromBacklogMutation = useRemoveFromBacklogMutation();

  const addToBacklogMutation = useAddToBacklogMutation();

  const rowData = row.original as Partial<App.Platform.Data.GameListEntry>;
  const gameId = rowData?.game?.id ?? 0;
  const gameTitle = rowData?.game?.title ?? '';
  const isInBacklog = rowData?.isInBacklog ?? false;

  // TODO put this in a hook? useBacklog()?
  const addToBacklog = (gameId: number, options?: Partial<{ isUndo: boolean }>) => {
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

  // TODO put this in a hook? useBacklog()?
  const removeFromBacklog = (gameId: number) => {
    toastMessage.promise(removeFromBacklogMutation.mutateAsync(gameId), {
      action: {
        label: 'Undo',
        onClick: () => addToBacklog(gameId, { isUndo: true }),
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

  const handleToggleFromBacklogClick = () => {
    // This should never happen.
    if (!gameId) {
      throw new Error('No game ID.');
    }

    if (!auth?.user) {
      // TODO handle user unauthenticated
      return;
    }

    if (isInBacklog) {
      removeFromBacklog(gameId);
    } else {
      addToBacklog(gameId);
    }
  };

  return (
    <BaseTooltip>
      <BaseTooltipTrigger asChild>
        <div className="flex justify-end">
          <BaseButton
            variant="ghost"
            className="group flex h-8 w-8 p-0 text-link"
            onClick={handleToggleFromBacklogClick}
          >
            <MdClose
              className={cn(
                'h-4 w-4 transition',
                isInBacklog ? 'text-red-600 light:text-red-500' : 'rotate-45',
              )}
            />

            <span className="sr-only">
              {isInBacklog ? 'Remove from Want To Play Games' : 'Add to Want to Play Games'}
            </span>
          </BaseButton>
        </div>
      </BaseTooltipTrigger>

      <BaseTooltipContent>
        <p>{isInBacklog ? 'Remove from Want to Play Games' : 'Add to Want to Play Games'}</p>
      </BaseTooltipContent>
    </BaseTooltip>
  );
}
