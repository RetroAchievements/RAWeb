import type { Row } from '@tanstack/react-table';
import { useEffect, useState } from 'react';
import { MdClose } from 'react-icons/md';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useWantToPlayGamesList } from '@/common/hooks/useWantToPlayGamesList';
import { cn } from '@/utils/cn';

/**
 * If the table row needs to have more than one action, it should go into a menu.
 * @see https://ui.shadcn.com/examples/tasks
 */

interface DataTableRowActionsProps<TData> {
  row: Row<TData>;
}

export function DataTableRowActions<TData>({ row }: DataTableRowActionsProps<TData>) {
  const { auth } = usePageProps();

  const { addToWantToPlayGamesList, isPending, removeFromWantToPlayGamesList } =
    useWantToPlayGamesList();

  const rowData = row.original as Partial<App.Platform.Data.GameListEntry>;
  const gameId = rowData?.game?.id ?? 0;
  const gameTitle = rowData?.game?.title ?? '';

  const isInBacklog = rowData?.isInBacklog ?? false;

  // We want to change the icon instantly for the user, even if the mutation is still running.
  const [isInBacklogOptimistic, setIsInBacklogOptimistic] = useState(isInBacklog);

  // When the actual `isInBacklog` changes, update the optimistic state accordingly.
  useEffect(() => {
    setIsInBacklogOptimistic(isInBacklog);
  }, [isInBacklog]);

  const handleToggleFromBacklogClick = () => {
    // This should never happen.
    if (!gameId) {
      throw new Error('No game ID.');
    }

    if (!auth?.user && typeof window !== 'undefined') {
      window.location.href = route('login');

      return;
    }

    setIsInBacklogOptimistic((prev) => !prev);

    const mutationPromise = isInBacklog
      ? removeFromWantToPlayGamesList(gameId, gameTitle)
      : addToWantToPlayGamesList(gameId, gameTitle);

    mutationPromise.catch(() => {
      setIsInBacklogOptimistic(isInBacklog);
    });
  };

  return (
    <BaseTooltip>
      <BaseTooltipTrigger asChild>
        <div className="flex justify-end">
          <BaseButton
            variant="ghost"
            className="group flex h-8 w-8 p-0 text-link disabled:!pointer-events-auto disabled:!opacity-100"
            onClick={handleToggleFromBacklogClick}
            disabled={isPending}
          >
            <MdClose
              className={cn(
                'h-4 w-4 transition-transform',
                'hover:text-neutral-50 disabled:!text-neutral-50 light:hover:text-neutral-900 light:disabled:text-neutral-900',
                isInBacklogOptimistic ? '' : 'rotate-45',
              )}
            />

            <span className="sr-only">
              {isInBacklogOptimistic
                ? 'Remove from Want To Play Games'
                : 'Add to Want to Play Games'}
            </span>
          </BaseButton>
        </div>
      </BaseTooltipTrigger>

      <BaseTooltipContent>
        <p>
          {isInBacklogOptimistic ? 'Remove from Want to Play Games' : 'Add to Want to Play Games'}
        </p>
      </BaseTooltipContent>
    </BaseTooltip>
  );
}
