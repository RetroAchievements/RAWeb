import type { ColumnDef, Table } from '@tanstack/react-table';
import { getCoreRowModel, useReactTable } from '@tanstack/react-table';
import userEvent from '@testing-library/user-event';
import type { FC } from 'react';

import { render, screen, waitFor } from '@/test';
import { createSystem, createZiggyProps } from '@/test/factories';

import { DataTableToolbar } from './DataTableToolbar';

// We'll use a sample data type.
interface Model {
  title: string;
  system: string;
  achievementsPublished: number;
}

const columns: ColumnDef<Model>[] = [
  {
    accessorKey: 'title',
    meta: { t_label: 'Title' },
  },
  {
    accessorKey: 'system',
    meta: { t_label: 'System' },
  },
  {
    accessorKey: 'achievementsPublished',
    meta: { t_label: 'Achievements Published' },
  },
];

const mockData: Model[] = [
  { title: 'Super Mario Bros.', system: 'NES/Famicom', achievementsPublished: 10 },
  { title: 'Perfect Dark', system: 'Nintendo 64', achievementsPublished: 20 },
];

interface DataTableToolbarHarnessProps {
  data?: Model[];
  unfilteredTotal?: number;
}

const DataTableToolbarHarness: FC<DataTableToolbarHarnessProps> = ({
  data = mockData,
  unfilteredTotal,
}) => {
  const table = useReactTable({
    data,
    columns,
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
    const selectedLabelEl = screen.getByTestId('filter-selected-label');

    expect(selectedLabelEl).toBeVisible();
    expect(selectedLabelEl).toHaveTextContent('GC');
  });

  it('given more than three options are selected, shows the selected count', async () => {
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
  });

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
});
