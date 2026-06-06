import { type Column, type ColumnDef, getCoreRowModel, useReactTable } from '@tanstack/react-table';
import userEvent from '@testing-library/user-event';
import type { FC } from 'react';

import i18n from '@/i18n-client';
import { render, screen } from '@/test';

import { DataTableColumnHeader } from './DataTableColumnHeader';

window.plausible = vi.fn();

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
  // eslint-disable-next-line react-hooks/incompatible-library -- https://github.com/TanStack/table/issues/5567
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

  it('given the column is sorted and plausible is available, tracks the sort event with the correct order', async () => {
    // ARRANGE
    const mockPlausible = vi.fn();
    window.plausible = mockPlausible;

    const column = {
      columnDef: {
        id: 'releaseDate',
        accessorKey: 'releaseDate',
        meta: { t_label: i18n.t('Release Date'), align: 'left' },
      },
      getCanSort: () => true,
      getIsSorted: () => false,
      getCanHide: () => false,
      toggleSorting: vi.fn(),
    } as unknown as Column<any, any>;

    render(<DataTableColumnHeaderTestHarness column={column} />);

    // ACT
    // ... click the sort button to open the menu ...
    const sortButton = screen.getByRole('button');
    await userEvent.click(sortButton);

    // get the first sort option (asc) and click it ...
    const sortMenuItems = screen.getAllByRole('menuitem');
    await userEvent.click(sortMenuItems[0]);

    // ASSERT
    expect(mockPlausible).toHaveBeenCalledWith('Game List Sort', {
      props: { order: 'releaseDate' },
    });
  });

  it('given plausible is not available, does not track the sort event', async () => {
    // ARRANGE
    window.plausible = undefined as any;

    const column = {
      columnDef: {
        id: 'title',
        accessorKey: 'title',
        meta: { t_label: i18n.t('Title'), align: 'left' },
      },
      getCanSort: () => true,
      getIsSorted: () => false,
      getCanHide: () => false,
      toggleSorting: vi.fn(),
    } as unknown as Column<any, any>;

    render(<DataTableColumnHeaderTestHarness column={column} />);

    // ACT
    const sortButton = screen.getByRole('button');
    await userEvent.click(sortButton);

    const sortMenuItems = screen.getAllByRole('menuitem');
    await userEvent.click(sortMenuItems[0]);

    // ASSERT
    expect(column.toggleSorting).toHaveBeenCalled();
  });

  it('given the column has no id and plausible is available, does not track the sort event', async () => {
    // ARRANGE
    const mockPlausible = vi.fn();
    window.plausible = mockPlausible;

    const column = {
      columnDef: {
        id: undefined,
        accessorKey: 'title',
        meta: { t_label: i18n.t('Title'), align: 'left' },
      },
      getCanSort: () => true,
      getIsSorted: () => false,
      getCanHide: () => false,
      toggleSorting: vi.fn(),
    } as unknown as Column<any, any>;

    render(<DataTableColumnHeaderTestHarness column={column} />);

    // ACT
    const sortButton = screen.getByRole('button');
    await userEvent.click(sortButton);

    const sortMenuItems = screen.getAllByRole('menuitem');
    await userEvent.click(sortMenuItems[0]);

    // ASSERT
    expect(mockPlausible).not.toHaveBeenCalled();
  });

  it('given the column is sorted descending and plausible is available, tracks the sort event with the prefixed order', async () => {
    // ARRANGE
    const mockPlausible = vi.fn();
    window.plausible = mockPlausible;

    const column = {
      columnDef: {
        id: 'title',
        accessorKey: 'title',
        meta: { t_label: i18n.t('Title'), align: 'left' },
      },
      getCanSort: () => true,
      getIsSorted: () => false,
      getCanHide: () => false,
      toggleSorting: vi.fn(),
    } as unknown as Column<any, any>;

    render(<DataTableColumnHeaderTestHarness column={column} />);

    // ACT
    // ... click the sort button to open the menu ...
    const sortButton = screen.getByRole('button');
    await userEvent.click(sortButton);

    // ... get the second sort option (desc) and click it ...
    const sortMenuItems = screen.getAllByRole('menuitem');
    await userEvent.click(sortMenuItems[1]);

    // ASSERT
    expect(mockPlausible).toHaveBeenCalledWith('Game List Sort', {
      props: { order: '-title' },
    });
  });
});
