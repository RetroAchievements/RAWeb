import type { Column, Table } from '@tanstack/react-table';
import type { FC, HTMLAttributes, ReactNode } from 'react';
import type { IconType } from 'react-icons/lib';
import { RxArrowDown, RxArrowUp, RxCaretSort, RxEyeNone } from 'react-icons/rx';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseDropdownMenu,
  BaseDropdownMenuContent,
  BaseDropdownMenuItem,
  BaseDropdownMenuSeparator,
  BaseDropdownMenuTrigger,
} from '@/common/components/+vendor/BaseDropdownMenu';
import { cn } from '@/utils/cn';

import { usePrefetchSort } from '../../hooks/usePrefetchSort';

type SortDirection = 'asc' | 'desc';
type SortConfig = {
  [key in SortDirection]: { label: string; icon?: IconType };
};

type SortConfigKind = 'default' | 'date' | 'quantity';

/**
 * The order of `asc` and `desc` determines the order they'll
 * appear in the menu as menuitems.
 */
const sortConfigs: Record<SortConfigKind, SortConfig> = {
  default: {
    asc: { label: 'Ascending (A - Z)' },
    desc: { label: 'Descending (Z - A)' },
  },
  date: {
    asc: { label: 'Earliest' },
    desc: { label: 'Latest' },
  },
  quantity: {
    desc: { label: 'More', icon: RxArrowUp },
    asc: { label: 'Less', icon: RxArrowDown },
  },
};

const defaultIcons = { asc: RxArrowUp, desc: RxArrowDown };

interface DataTableColumnHeaderProps<TData, TValue> extends HTMLAttributes<HTMLDivElement> {
  column: Column<TData, TValue>;
  table: Table<TData>;

  sortType?: SortConfigKind;
}

export function DataTableColumnHeader<TData, TValue>({
  className,
  column,
  table,
  sortType = 'default',
}: DataTableColumnHeaderProps<TData, TValue>): ReactNode {
  const { prefetchSort } = usePrefetchSort(table);

  if (!column.getCanSort()) {
    return <div className={cn(className)}>{column.columnDef.meta?.label}</div>;
  }

  const sortConfig = sortConfigs[sortType];

  const getIcon = (direction: 'asc' | 'desc'): IconType =>
    sortConfig[direction].icon || defaultIcons[direction];

  const getCurrentSortIcon = (): IconType => {
    const sortDirection = column.getIsSorted();

    if (sortDirection === false) {
      return RxCaretSort;
    }

    return getIcon(sortDirection);
  };

  const SortIcon = getCurrentSortIcon();

  return (
    <div
      className={cn(
        'flex items-center space-x-2',
        column.columnDef.meta?.align === 'right' ? 'justify-end' : '',
        column.columnDef.meta?.align === 'center' ? 'justify-center' : '',
        className,
      )}
    >
      <BaseDropdownMenu>
        <BaseDropdownMenuTrigger asChild>
          <BaseButton
            variant="ghost"
            size="sm"
            className="data-[state=open]:bg-accent -ml-3 h-8 !transform-none focus-visible:!ring-0 focus-visible:!ring-offset-0"
            data-testid={`column-header-${column.columnDef.meta?.label}`}
          >
            <span>{column.columnDef.meta?.label}</span>
            <SortIcon className="ml-1 h-4 w-4" />
          </BaseButton>
        </BaseDropdownMenuTrigger>

        <BaseDropdownMenuContent align="start">
          {(Object.keys(sortConfig) as SortDirection[]).map((direction) => (
            <SortMenuItem
              key={direction}
              direction={direction}
              icon={getIcon(direction)}
              label={sortConfig[direction].label}
              onClick={() => column.toggleSorting(direction === 'desc')}
              onMouseEnter={() => prefetchSort(column.columnDef.id, direction)}
            />
          ))}

          {column.getCanHide() ? (
            <>
              <BaseDropdownMenuSeparator />

              <BaseDropdownMenuItem onClick={() => column.toggleVisibility(false)}>
                <RxEyeNone className="text-muted-foreground/70 mr-2 h-3.5 w-3.5" />
                Hide
              </BaseDropdownMenuItem>
            </>
          ) : null}
        </BaseDropdownMenuContent>
      </BaseDropdownMenu>
    </div>
  );
}

interface SortMenuItemProps {
  direction: 'asc' | 'desc';
  icon: IconType;
  label: string;
  onClick: () => void;

  onMouseEnter?: () => void;
}

const SortMenuItem: FC<SortMenuItemProps> = ({ icon: Icon, label, onClick, onMouseEnter }) => {
  return (
    <BaseDropdownMenuItem onClick={onClick} onMouseEnter={onMouseEnter}>
      <Icon className="text-muted-foreground/70 mr-2 h-3.5 w-3.5" />
      {label}
    </BaseDropdownMenuItem>
  );
};
