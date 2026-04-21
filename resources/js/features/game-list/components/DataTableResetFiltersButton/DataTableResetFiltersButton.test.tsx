import type { ColumnFiltersState, SortingState } from '@tanstack/react-table';
import { getCoreRowModel, useReactTable } from '@tanstack/react-table';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import type { FC } from 'react';
import { useState } from 'react';

import { render, screen, waitFor } from '@/test';
import { createPaginatedData } from '@/test/factories';

import { DataTableResetFiltersButton } from './DataTableResetFiltersButton';

interface DataTableResetFiltersButtonTestHarnessProps {
  columnFilters?: ColumnFiltersState;
  sorting?: SortingState;
}

const DataTableResetFiltersButtonTestHarness: FC<DataTableResetFiltersButtonTestHarnessProps> = ({
  columnFilters = [],
  sorting = [{ id: 'title', desc: false }],
}) => {
  const [currentColumnFilters, setCurrentColumnFilters] = useState(columnFilters);
  const [currentSorting, setCurrentSorting] = useState(sorting);

  // eslint-disable-next-line react-hooks/incompatible-library -- https://github.com/TanStack/table/issues/5567
  const table = useReactTable({
    data: [],
    columns: [],
    state: {
      columnFilters: currentColumnFilters,
      pagination: { pageIndex: 0, pageSize: 25 },
      sorting: currentSorting,
    },
    onColumnFiltersChange: setCurrentColumnFilters,
    onSortingChange: setCurrentSorting,
    getCoreRowModel: getCoreRowModel(),
  });

  return (
    <div>
      <DataTableResetFiltersButton
        table={table}
        defaultColumnFilters={[{ id: 'system', value: ['supported'] }]}
        defaultColumnSort={{ id: 'title', desc: false }}
      />

      <p data-testid="current-column-filters-state">
        {JSON.stringify(table.getState().columnFilters)}
      </p>
      <p data-testid="current-sorting-state">{JSON.stringify(table.getState().sorting)}</p>
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

    render(
      <DataTableResetFiltersButtonTestHarness
        columnFilters={[{ id: 'progress', value: ['mastered'] }]}
        sorting={[{ id: 'playersTotal', desc: true }]}
      />,
    );

    // ACT
    await userEvent.hover(screen.getByRole('button', { name: /reset/i }));

    // ASSERT
    await waitFor(() => {
      expect(getSpy).toHaveBeenCalledOnce();
    });

    expect(getSpy).toHaveBeenCalledWith([
      'api.game.index',
      {
        'page[number]': 1,
        'page[size]': 25,
        'filter[system]': 'supported',
        sort: 'title',
      },
    ]);
  });

  it('given the user clicks the button, resets filters and sorting to their defaults', async () => {
    // ARRANGE
    render(
      <DataTableResetFiltersButtonTestHarness
        columnFilters={[{ id: 'progress', value: ['mastered'] }]}
        sorting={[{ id: 'playersTotal', desc: true }]}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /reset/i }));

    // ASSERT
    expect(screen.getByTestId('current-column-filters-state')).toHaveTextContent(
      '[{"id":"system","value":["supported"]}]',
    );
    expect(screen.getByTestId('current-sorting-state')).toHaveTextContent(
      '[{"id":"title","desc":false}]',
    );
  });
});
