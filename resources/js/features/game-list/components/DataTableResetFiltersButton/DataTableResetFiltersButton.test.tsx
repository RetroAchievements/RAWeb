import type { ColumnFiltersState } from '@tanstack/react-table';
import { getCoreRowModel, useReactTable } from '@tanstack/react-table';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import type { FC } from 'react';

import { render, screen, waitFor } from '@/test';
import { createPaginatedData } from '@/test/factories';

import { DataTableResetFiltersButton } from './DataTableResetFiltersButton';

interface DataTableResetFiltersButtonTestHarnessProps {
  columnFilters?: ColumnFiltersState;
}

const DataTableResetFiltersButtonTestHarness: FC<DataTableResetFiltersButtonTestHarnessProps> = ({
  columnFilters = [],
}) => {
  const table = useReactTable({
    data: [],
    columns: [],
    state: {
      columnFilters,
    },
    getCoreRowModel: getCoreRowModel(),
  });

  return (
    <div>
      <DataTableResetFiltersButton table={table} />

      <p data-testid="current-column-filters-state">
        {JSON.stringify(table.getState().columnFilters)}
      </p>
    </div>
  );
};

describe('Component: DataTableResetFiltersButton', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<DataTableResetFiltersButtonTestHarness />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user hovers the button, prefetches the reset filters result', async () => {
    // ARRANGE
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    render(<DataTableResetFiltersButtonTestHarness />);

    // ACT
    await userEvent.hover(screen.getByRole('button', { name: /reset/i }));

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledOnce();
    });

    expect(getSpy).toHaveBeenCalledWith(['api.game.index', { 'page[number]': 1, sort: null }]);
  });
});
