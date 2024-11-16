import type { ColumnDef } from '@tanstack/react-table';

import { DataTableRowActions } from '../../components/DataTableRowActions';

export function buildRowActionsColumnDef(options: {
  shouldAnimateBacklogIconOnChange: boolean;
}): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'actions',
    enableSorting: false,

    cell: ({ row }) => (
      // Prevent spurious tooltip re-openings after a toast pops and closes.
      <div key={row.original.game.id} onFocusCapture={(event) => event.stopPropagation()}>
        <DataTableRowActions
          row={row}
          shouldAnimateBacklogIconOnChange={options.shouldAnimateBacklogIconOnChange}
        />
      </div>
    ),
  };
}
