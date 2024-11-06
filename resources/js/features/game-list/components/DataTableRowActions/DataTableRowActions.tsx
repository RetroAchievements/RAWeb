import type { Row } from '@tanstack/react-table';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useState } from 'react';
import { MdClose } from 'react-icons/md';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { cn } from '@/utils/cn';

import { useGameBacklogState } from '../GameListItems/useGameBacklogState';

/**
 * If the table row needs to have more than one action, it should go into a menu.
 * @see https://ui.shadcn.com/examples/tasks
 */

interface DataTableRowActionsProps<TData> {
  row: Row<TData>;

  /**
   * If set to `false`, the add/remove backlog icon will not animate on click.
   * This is useful if the row is going to be removed from the DOM, ie:
   * viewing a user's backlog and them removing a game from it.
   */
  shouldAnimateBacklogIconOnChange?: boolean;
}

export function DataTableRowActions<TData>({
  row,
  shouldAnimateBacklogIconOnChange = true,
}: DataTableRowActionsProps<TData>) {
  const { t } = useLaravelReactI18n();

  const rowData = row.original as Partial<App.Platform.Data.GameListEntry>;
  const gameId = rowData?.game?.id ?? 0;

  const { isPending, toggleBacklog, isInBacklogMaybeOptimistic } = useGameBacklogState({
    game: { id: rowData?.game?.id ?? 0, title: rowData?.game?.title ?? '' },
    isInitiallyInBacklog: rowData?.isInBacklog ?? false,
    shouldShowToasts: true,
    shouldUpdateOptimistically: shouldAnimateBacklogIconOnChange,
  });

  const [initialRotationClassName] = useState(
    isInBacklogMaybeOptimistic ? '!rotate-0' : '!rotate-45',
  );

  // This should never happen.
  if (!gameId) {
    throw new Error('No game ID.');
  }

  return (
    <BaseTooltip>
      <BaseTooltipTrigger asChild>
        <div className="flex justify-end">
          <BaseButton
            variant="ghost"
            className="group flex h-8 w-8 p-0 text-link disabled:!pointer-events-auto disabled:!opacity-100"
            onClick={() => toggleBacklog()}
            disabled={isPending}
            aria-label={
              isInBacklogMaybeOptimistic
                ? t('Remove from Want To Play Games')
                : t('Add to Want to Play Games')
            }
            data-testid={`toggle-${isInBacklogMaybeOptimistic}`}
          >
            <MdClose
              className={cn(
                'h-4 w-4',
                'hover:text-neutral-50 disabled:!text-neutral-50 light:hover:text-neutral-900 light:disabled:text-neutral-900',

                !shouldAnimateBacklogIconOnChange ? initialRotationClassName : null,
                shouldAnimateBacklogIconOnChange ? 'transition-transform' : null,

                isInBacklogMaybeOptimistic ? 'rotate-0' : 'rotate-45',
              )}
            />
          </BaseButton>
        </div>
      </BaseTooltipTrigger>

      <BaseTooltipContent>
        <p>
          {isInBacklogMaybeOptimistic
            ? t('Remove from Want to Play Games')
            : t('Add to Want to Play Games')}
        </p>
      </BaseTooltipContent>
    </BaseTooltip>
  );
}
