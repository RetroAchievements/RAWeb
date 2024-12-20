import type { Table } from '@tanstack/react-table';
import { useTranslation } from 'react-i18next';
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

interface DataTableColumnsToggleProps<TData> {
  table: Table<TData>;
}

export function DataTableColumnsToggle<TData>({ table }: DataTableColumnsToggleProps<TData>) {
  const { t } = useTranslation();

  return (
    <BaseDropdownMenu>
      <BaseDropdownMenuTrigger asChild>
        <BaseButton size="sm" className="gap-1.5">
          <RxMixerHorizontal className="h-4 w-4" />
          {t('Columns')}
        </BaseButton>
      </BaseDropdownMenuTrigger>

      <BaseDropdownMenuContent align="end">
        <BaseDropdownMenuLabel>{t('Toggle columns')}</BaseDropdownMenuLabel>

        <BaseDropdownMenuSeparator />

        {table
          .getAllColumns()
          .filter((column) => typeof column.accessorFn !== 'undefined' && column.getCanHide())
          .map((column) => {
            return (
              <BaseDropdownMenuCheckboxItem
                key={`column-toggle-${column.id}`}
                checked={column.getIsVisible()}
                onCheckedChange={(value) => column.toggleVisibility(!!value)}
              >
                {column.columnDef.meta?.t_label}
              </BaseDropdownMenuCheckboxItem>
            );
          })}
      </BaseDropdownMenuContent>
    </BaseDropdownMenu>
  );
}
