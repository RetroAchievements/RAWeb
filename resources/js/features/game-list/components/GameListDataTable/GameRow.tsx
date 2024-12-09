import { type Cell, flexRender, type Row } from '@tanstack/react-table';
import type { FC } from 'react';

import { BaseTableCell, BaseTableRow } from '@/common/components/+vendor/BaseTable';
import { cn } from '@/common/utils/cn';

interface GameRowProps {
  row: Row<App.Platform.Data.GameListEntry>;
  shouldShowGroups: boolean;
}

export const GameRow: FC<GameRowProps> = ({ row, shouldShowGroups }) => {
  return (
    <BaseTableRow
      className={cn(
        shouldShowGroups &&
          '!bg-transparent first:!mt-0 hover:!bg-gray-500/10 light:!bg-white black:!bg-black',
      )}
    >
      {row.getVisibleCells().map((cell: Cell<App.Platform.Data.GameListEntry, unknown>) => (
        <BaseTableCell
          key={cell.id}
          className={cn(cell.column.columnDef.meta?.align === 'right' ? 'pr-6 text-right' : null)}
        >
          {flexRender(cell.column.columnDef.cell, cell.getContext())}
        </BaseTableCell>
      ))}
    </BaseTableRow>
  );
};
