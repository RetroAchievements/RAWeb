import type { VisibilityState } from '@tanstack/react-table';

import type { TicketListColumnId } from '../models';

export function toggleTicketListColumnOverride(
  columnVisibilityOverrides: VisibilityState,
  defaultColumnVisibility: VisibilityState,
  columnId: TicketListColumnId,
): VisibilityState {
  const currentlyVisible = columnVisibilityOverrides[columnId] ?? defaultColumnVisibility[columnId];
  const nextIsVisible = !currentlyVisible;

  const nextOverrides = { ...columnVisibilityOverrides };

  if (nextIsVisible === defaultColumnVisibility[columnId]) {
    delete nextOverrides[columnId];
  } else {
    nextOverrides[columnId] = nextIsVisible;
  }

  return nextOverrides;
}
