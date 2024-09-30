import type { Row } from '@tanstack/react-table';
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

  const handleToggleFromBacklogClick = () => {
    // This should never happen.
    if (!gameId) {
      throw new Error('No game ID.');
    }

    if (!auth?.user && typeof window !== 'undefined') {
      window.location.href = route('login');

      return;
    }

    if (isInBacklog) {
      removeFromWantToPlayGamesList(gameId, gameTitle);
    } else {
      addToWantToPlayGamesList(gameId, gameTitle);
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
            disabled={isPending}
          >
            <MdClose
              className={cn(
                'h-4 w-4 transition',
                'hover:text-neutral-50 disabled:text-neutral-50 light:hover:text-neutral-900 light:disabled:text-neutral-900',
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
