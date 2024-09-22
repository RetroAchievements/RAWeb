import { useQueryClient } from '@tanstack/react-query';
import type { Row } from '@tanstack/react-table';
import { RxCross2 } from 'react-icons/rx';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { useAddToBacklogMutation } from '@/common/hooks/useAddToBacklogMutation';
import { useRemoveFromBacklogMutation } from '@/common/hooks/useRemoveFromBacklogMutation';

/**
 * If the table row needs to have more than one action, it should go into a menu.
 * @see https://ui.shadcn.com/examples/tasks
 */

interface DataTableRowActionsProps<TData> {
  row: Row<TData>;
}

export function DataTableRowActions<TData>({ row }: DataTableRowActionsProps<TData>) {
  const queryClient = useQueryClient();

  const removeFromBacklogMutation = useRemoveFromBacklogMutation();

  const addToBacklogMutation = useAddToBacklogMutation();

  const rowData = row.original as { game?: App.Platform.Data.Game };
  const gameId = rowData?.game?.id ?? 0;
  const gameTitle = rowData?.game?.title ?? '';

  const handleRestoreToBacklogClick = () => {
    toastMessage.promise(addToBacklogMutation.mutateAsync(gameId), {
      loading: 'Removing...',
      success: () => {
        // Trigger a refetch of the current table page data and bust the entire cache.
        queryClient.invalidateQueries({ queryKey: ['data'] });

        return `Restored ${gameTitle}!`;
      },
      error: 'Something went wrong.',
    });
  };

  const handleRemoveFromBacklogClick = () => {
    if (gameId) {
      toastMessage.promise(removeFromBacklogMutation.mutateAsync(gameId), {
        action: {
          label: 'Undo',
          onClick: handleRestoreToBacklogClick,
        },
        loading: 'Removing...',
        success: () => {
          // Trigger a refetch of the current table page data and bust the entire cache.
          queryClient.invalidateQueries({ queryKey: ['data'] });

          return `Removed ${gameTitle}!`;
        },
        error: 'Something went wrong.',
      });
    }
  };

  return (
    <BaseTooltip>
      <BaseTooltipTrigger asChild>
        <BaseButton
          variant="ghost"
          className="flex h-8 w-8 p-0 text-link"
          onClick={handleRemoveFromBacklogClick}
        >
          <RxCross2 className="h-4 w-4" />
          <span className="sr-only">Remove from backlog</span>
        </BaseButton>
      </BaseTooltipTrigger>

      <BaseTooltipContent>
        <p>Remove</p>
      </BaseTooltipContent>
    </BaseTooltip>
  );
}
