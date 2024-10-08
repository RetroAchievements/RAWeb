import type { ColumnDef } from '@tanstack/react-table';

import { DataTableRowActions } from '../../components/DataTableRowActions';

export function buildRowActionsColumnDef(): ColumnDef<App.Platform.Data.GameListEntry> {
  return {
    id: 'actions',
    cell: ({ row }) => (
      // Prevent spurious tooltip re-openings after a toast pops and closes.
      <div onFocusCapture={(event) => event.stopPropagation()}>
        <DataTableRowActions row={row} />
      </div>
    ),
  };
}
