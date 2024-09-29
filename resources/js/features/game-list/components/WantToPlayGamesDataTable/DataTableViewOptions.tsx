import type { Table } from '@tanstack/react-table';
import { RxMixerHorizontal } from 'react-icons/rx';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseDropdownMenu,
  BaseDropdownMenuCheckboxItem,
  BaseDropdownMenuContent,
  BaseDropdownMenuLabel,
  BaseDropdownMenuSeparator,
  BaseDropdownMenuTrigger,
} from '@/common/components/+vendor/BaseDropdownMenu';

interface DataTableViewOptionsProps<TData> {
  table: Table<TData>;
}

export function DataTableViewOptions<TData>({ table }: DataTableViewOptionsProps<TData>) {
  return (
    <BaseDropdownMenu>
      <BaseDropdownMenuTrigger asChild>
        <BaseButton size="sm" className="gap-2">
          <RxMixerHorizontal className="h-4 w-4" />
          View
        </BaseButton>
      </BaseDropdownMenuTrigger>

      <BaseDropdownMenuContent align="end">
        <BaseDropdownMenuLabel>Toggle columns</BaseDropdownMenuLabel>

        <BaseDropdownMenuSeparator />

        {table
          .getAllColumns()
          .filter((column) => typeof column.accessorFn !== 'undefined' && column.getCanHide())
          .map((column) => {
            return (
              <BaseDropdownMenuCheckboxItem
                key={`column-toggle-${column.id}`}
                className="capitalize"
                checked={column.getIsVisible()}
                onCheckedChange={(value) => column.toggleVisibility(!!value)}
              >
                {column.columnDef.meta?.label}
              </BaseDropdownMenuCheckboxItem>
            );
          })}
      </BaseDropdownMenuContent>
    </BaseDropdownMenu>
  );
}
