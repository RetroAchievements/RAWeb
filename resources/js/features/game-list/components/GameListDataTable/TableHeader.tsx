import { flexRender, type Table } from '@tanstack/react-table';
import type { FC } from 'react';

import {
  BaseTableHead,
  BaseTableHeader,
  BaseTableRow,
} from '@/common/components/+vendor/BaseTable';
import { cn } from '@/common/utils/cn';

interface TableHeaderProps {
  table: Table<App.Platform.Data.GameListEntry>;
  visibleColumnCount: number;
}

export const TableHeader: FC<TableHeaderProps> = ({ table, visibleColumnCount }) => {
  return (
    <BaseTableHeader>
      {table.getHeaderGroups().map((headerGroup) => (
        <BaseTableRow
          key={headerGroup.id}
          className={cn(
            'do-not-highlight bg-embed lg:sticky lg:top-[41px] lg:z-10',

            visibleColumnCount > 8 ? 'lg:!top-0' : '',
            visibleColumnCount > 10 ? 'xl:!top-0' : '',
          )}
        >
          {headerGroup.headers.map((header) => (
            <BaseTableHead key={header.id}>
              {flexRender(header.column.columnDef.header, header.getContext())}
            </BaseTableHead>
          ))}
        </BaseTableRow>
      ))}
    </BaseTableHeader>
  );
};
