import type { ColumnDef, Table } from '@tanstack/react-table';
import { getCoreRowModel, useReactTable } from '@tanstack/react-table';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import type { FC } from 'react';

import i18n from '@/i18n-client';
import { render, screen, waitFor } from '@/test';
import { createPaginatedData, createSystem, createZiggyProps } from '@/test/factories';

import { DataTableToolbar } from './DataTableToolbar';

// Suppress "Column with id 'achievementsPublished' does not exist".
console.error = vi.fn();

vi.mock('./RandomGameButton');

// We'll use a sample data type.
interface Model {
  title: string;
  system: string;
  achievementsPublished: number;
}

const mockColumns: ColumnDef<Model>[] = [
  {
    accessorKey: 'title',
    meta: { t_label: i18n.t('Title') },
  },
  {
    accessorKey: 'system',
    meta: { t_label: i18n.t('System') },
  },
  {
    accessorKey: 'achievementsPublished',
    meta: { t_label: i18n.t('Achievements') },
  },
];

const mockData: Model[] = [
  { title: 'Super Mario Bros.', system: 'NES/Famicom', achievementsPublished: 10 },
  { title: 'Perfect Dark', system: 'Nintendo 64', achievementsPublished: 20 },
];

interface DataTableToolbarHarnessProps {
  columns?: ColumnDef<Model>[];
  data?: Model[];
  unfilteredTotal?: number;
}

const DataTableToolbarHarness: FC<DataTableToolbarHarnessProps> = ({
  columns = mockColumns,
  data = mockData,
  unfilteredTotal,
}) => {
  const table = useReactTable({
    data,
    columns,
    state: {
      pagination: { pageIndex: 0, pageSize: 25 },
    },
    rowCount: data.length ?? 0,
    getCoreRowModel: getCoreRowModel(),
  });

  return (
    <DataTableToolbar
      table={table as Table<unknown>}
      unfilteredTotal={unfilteredTotal ?? data.length}
    />
  );
};

describe('Component: DataTableToolbar', () => {
  beforeEach(() => {
    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<DataTableToolbarHarness />, {
      pageProps: { ziggy: createZiggyProps({ device: 'desktop' }) },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no filterable system options, does not crash', async () => {
    // ARRANGE
    const { container } = render(<DataTableToolbarHarness />, {
      pageProps: { ziggy: createZiggyProps({ device: 'desktop' }) },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are filterable system options, has a working System filter', async () => {
    // ARRANGE
    render(<DataTableToolbarHarness />, {
      pageProps: {
        filterableSystemOptions: [
          createSystem({ name: 'Nintendo 64', nameShort: 'N64' }),
          createSystem({ name: 'NES/Famicom', nameShort: 'NES' }),
          createSystem({ name: 'GameCube', nameShort: 'GC' }),
        ],
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /system/i }));
    await userEvent.click(screen.getByRole('option', { name: /GameCube/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByTestId('filter-selected-label')).toBeVisible();
    });

    expect(screen.getByTestId('filter-selected-label')).toHaveTextContent('GC');
  });

  it(
    'given more than three options are selected, shows the selected count',
    { retry: 2, timeout: 15000 },
    async () => {
      // ARRANGE
      render(<DataTableToolbarHarness />, {
        pageProps: {
          filterableSystemOptions: [
            createSystem({ name: 'Nintendo 64', nameShort: 'N64' }),
            createSystem({ name: 'NES/Famicom', nameShort: 'NES' }),
            createSystem({ name: 'GameCube', nameShort: 'GC' }),
          ],
          ziggy: createZiggyProps({ device: 'desktop' }),
        },
      });

      // ACT
      await userEvent.click(screen.getByRole('button', { name: /system/i }));

      // Ensure the options are visible before we start trying to click on them.
      await waitFor(() => {
        screen.getByRole('option', { name: /NES/i });
      });

      await userEvent.click(screen.getByRole('option', { name: /Nintendo 64/i }));
      await userEvent.click(screen.getByRole('option', { name: /NES/i }));
      await userEvent.click(screen.getByRole('option', { name: /GameCube/i }));

      // ASSERT
      await waitFor(
        () => {
          expect(screen.getByText(/3 selected/i)).toBeVisible();
        },
        { timeout: 3000 },
      );
    },
  );

  it('given the unfiltered total is different than the row count, displays both counts to the user', () => {
    // ARRANGE
    render(<DataTableToolbarHarness unfilteredTotal={10} />, {
      pageProps: {
        filterableSystemOptions: [
          createSystem({ name: 'Nintendo 64', nameShort: 'N64' }),
          createSystem({ name: 'NES/Famicom', nameShort: 'NES' }),
          createSystem({ name: 'GameCube', nameShort: 'GC' }),
        ],
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.getByText(/2 of 10 games/i)).toBeVisible();
  });

  it('given the unfiltered total is the same as the current row count, displays just a single count', () => {
    // ARRANGE
    render(<DataTableToolbarHarness unfilteredTotal={2} />, {
      pageProps: {
        filterableSystemOptions: [
          createSystem({ name: 'Nintendo 64', nameShort: 'N64' }),
          createSystem({ name: 'NES/Famicom', nameShort: 'NES' }),
          createSystem({ name: 'GameCube', nameShort: 'GC' }),
        ],
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.getByText(/2 games/i)).toBeVisible();
    expect(screen.queryByText(/2 of 2 games/i)).not.toBeInTheDocument();
  });

  it('given the unfiltered total is the same as the current row count and both counts are zero, does not crash', () => {
    // ARRANGE
    render(<DataTableToolbarHarness unfilteredTotal={0} data={[]} />, {
      pageProps: {
        filterableSystemOptions: [
          createSystem({ name: 'Nintendo 64', nameShort: 'N64' }),
          createSystem({ name: 'NES/Famicom', nameShort: 'NES' }),
          createSystem({ name: 'GameCube', nameShort: 'GC' }),
        ],
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ASSERT
    expect(screen.getByText(/0 games/i)).toBeVisible();
    expect(screen.queryByText(/0 of 0 games/i)).not.toBeInTheDocument();
  });

  it('given the user has filters set, shows a Reset button', async () => {
    // ARRANGE
    render(<DataTableToolbarHarness />, {
      pageProps: {
        filterableSystemOptions: [
          createSystem({ name: 'Nintendo 64', nameShort: 'N64' }),
          createSystem({ name: 'NES/Famicom', nameShort: 'NES' }),
          createSystem({ name: 'GameCube', nameShort: 'GC' }),
        ],
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /system/i }));
    await userEvent.click(screen.getByRole('option', { name: /GameCube/i }));

    // ASSERT
    expect(screen.getByRole('button', { name: /reset/i })).toBeVisible();
  });

  it('given the user hovers over the Reset button, initiates a prefetch for the destination data', async () => {
    // ARRANGE
    const getSpy = vi.spyOn(axios, 'get').mockResolvedValueOnce({ data: createPaginatedData([]) });

    render(<DataTableToolbarHarness />, {
      pageProps: {
        filterableSystemOptions: [
          createSystem({ name: 'Nintendo 64', nameShort: 'N64' }),
          createSystem({ name: 'NES/Famicom', nameShort: 'NES' }),
          createSystem({ name: 'GameCube', nameShort: 'GC' }),
        ],
        ziggy: createZiggyProps({ device: 'desktop' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /system/i }));
    await userEvent.click(screen.getByRole('option', { name: /GameCube/i }));

    await userEvent.hover(screen.getByRole('button', { name: /reset/i }));
    // just a hover, no click

    // ASSERT
    expect(getSpy).toHaveBeenCalledOnce();
    expect(getSpy).toHaveBeenCalledWith([
      'api.game.index',
      {
        'page[number]': 1,
        'page[size]': 25,
        sort: null,
      },
    ]);
  });

  it('given the table has no achievements published column, does not show the "Has achievements" filter', () => {
    // ARRANGE
    const columnsWithoutAchievements: ColumnDef<Model>[] = [
      {
        accessorKey: 'title',
        meta: { t_label: 'Title' },
      },
      {
        accessorKey: 'system',
        meta: { t_label: 'System' },
      },
      // !! no achievementsPublished column
    ];

    render(<DataTableToolbarHarness columns={columnsWithoutAchievements} />, {
      pageProps: {
        ziggy: createZiggyProps({ device: 'desktop' }),
        filterableSystemOptions: [createSystem({ name: 'Nintendo 64', nameShort: 'N64' })],
      },
    });

    // ASSERT
    expect(screen.queryByRole('button', { name: /has achievements/i })).not.toBeInTheDocument();
  });
});
