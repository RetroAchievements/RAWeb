import { type Column, type ColumnDef, getCoreRowModel, useReactTable } from '@tanstack/react-table';
import type { FC } from 'react';

import i18n from '@/i18n-client';
import { render, screen } from '@/test';

import { DataTableColumnHeader } from './DataTableColumnHeader';

interface DataTableColumnHeaderTestHarnessProps {
  column: Column<any, any>;

  columns?: ColumnDef<any>[];
  data?: any[];
}

// We have to create a test harness because the component relies on the
// useReactTable() hook, which itself cannot be called from a test function.
const DataTableColumnHeaderTestHarness: FC<DataTableColumnHeaderTestHarnessProps> = ({
  column,
  columns = [],
  data = [],
}) => {
  const table = useReactTable({
    data,
    columns,
    getCoreRowModel: getCoreRowModel(),
  });

  return <DataTableColumnHeader table={table} column={column} />;
};

describe('Component: DataTableColumnHeader', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const column = {
      columnDef: {
        accessorKey: 'title',
        meta: { t_label: i18n.t('Title'), align: 'left' },
      },
      getCanSort: () => false,
      getIsSorted: () => false,
      getCanHide: () => false,
    } as Column<any, any>;

    const { container } = render(<DataTableColumnHeaderTestHarness column={column} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the column cannot be sorted, is not clickable', () => {
    // ARRANGE
    const column = {
      columnDef: {
        accessorKey: 'title',
        meta: { t_label: i18n.t('Title'), align: 'left' },
      },
      getCanSort: () => false,
      getIsSorted: () => false,
      getCanHide: () => false,
    } as Column<any, any>;

    render(<DataTableColumnHeaderTestHarness column={column} />);

    // ASSERT
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  it('given the column can be sorted, is clickable', () => {
    // ARRANGE
    const column = {
      columnDef: {
        accessorKey: 'title',
        meta: { t_label: i18n.t('Title'), align: 'left' },
      },
      getCanSort: () => true,
      getIsSorted: () => false,
      getCanHide: () => false,
    } as Column<any, any>;

    render(<DataTableColumnHeaderTestHarness column={column} />);

    // ASSERT
    expect(screen.getByRole('button')).toBeVisible();
  });
});
