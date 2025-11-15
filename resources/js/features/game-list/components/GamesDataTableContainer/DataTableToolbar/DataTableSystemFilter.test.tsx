import type { ColumnDef, Table } from '@tanstack/react-table';
import { getCoreRowModel, useReactTable } from '@tanstack/react-table';
import userEvent from '@testing-library/user-event';
import type { FC } from 'react';

import { render, screen, waitFor, within } from '@/test';
import { createSystem } from '@/test/factories';
import type { TranslatedString } from '@/types/i18next';

import { DataTableSystemFilter } from './DataTableSystemFilter';

// Suppress "[Table] Column with id 'system' does not exist".
console.error = vi.fn();

interface Model {
  system: string;
}

const mockColumns: ColumnDef<Model>[] = [
  {
    accessorKey: 'system',
    meta: { t_label: 'System' as TranslatedString },
  },
];

const mockData: Model[] = [
  { system: 'NES/Famicom' },
  { system: 'Nintendo 64' },
  //
];

type DataTableSystemFilterHarnessProps = Partial<{
  columns: ColumnDef<Model>[];
  data: Model[];
  filterableSystemOptions: App.Platform.Data.System[];
  defaultOptionLabel: TranslatedString;
  defaultOptionValue: 'supported' | 'all';
  includeDefaultOption: boolean;
  isSingleSelect: boolean;
  variant: 'base' | 'drawer';
}>;

const DataTableSystemFilterHarness: FC<DataTableSystemFilterHarnessProps> = ({
  defaultOptionLabel,
  variant,
  columns = mockColumns,
  data = mockData,
  filterableSystemOptions = [],
  defaultOptionValue = 'supported',
  includeDefaultOption = false,
  isSingleSelect = false,
}) => {
  // eslint-disable-next-line react-hooks/incompatible-library -- https://github.com/TanStack/table/issues/5567
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
    <DataTableSystemFilter
      table={table as Table<unknown>}
      filterableSystemOptions={filterableSystemOptions}
      defaultOptionLabel={defaultOptionLabel}
      defaultOptionValue={defaultOptionValue}
      includeDefaultOption={includeDefaultOption}
      isSingleSelect={isSingleSelect}
      variant={variant}
    />
  );
};

describe('Component: DataTableSystemFilter', () => {
  beforeEach(() => {
    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<DataTableSystemFilterHarness />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given systems are provided, sorts them alphabetically by name', async () => {
    // ARRANGE
    render(
      <DataTableSystemFilterHarness
        filterableSystemOptions={[
          createSystem({ id: 3, name: 'Zebra System', nameShort: 'ZEB' }),
          createSystem({ id: 1, name: 'Apple System', nameShort: 'APP' }),
          createSystem({ id: 2, name: 'Banana System', nameShort: 'BAN' }),
        ]}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /system/i }));

    // ASSERT
    const listbox = screen.getByRole('listbox');
    const options = within(listbox).getAllByRole('option');

    expect(options[0]).toHaveTextContent(/apple system/i);
    expect(options[1]).toHaveTextContent(/banana system/i);
    expect(options[2]).toHaveTextContent(/zebra system/i);
  });

  it('given includeDefaultOption is true, isSingleSelect is true, and defaultOptionValue is "supported", shows both options with "supported" as default', async () => {
    // ARRANGE
    render(
      <DataTableSystemFilterHarness
        filterableSystemOptions={[createSystem({ name: 'Nintendo 64', nameShort: 'N64' })]}
        includeDefaultOption={true} // !!
        isSingleSelect={true} // !!
        defaultOptionValue="supported" // !!
        defaultOptionLabel={'Only supported systems' as TranslatedString}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /system/i }));

    // ASSERT
    const supportedOption = screen.getByRole('option', { name: /only supported systems/i });
    const allOption = screen.getByRole('option', { name: /all systems/i });

    expect(supportedOption).toBeVisible();
    expect(allOption).toBeVisible();

    // ... the "Only supported systems" option should be marked as the default ...
    expect(within(supportedOption).getByRole('img', { hidden: true })).toBeVisible();
    expect(within(allOption).queryByRole('img', { hidden: true })).not.toBeInTheDocument();
  });

  it('given includeDefaultOption is true, isSingleSelect is true, and defaultOptionValue is "all", shows both options with "all" as default', async () => {
    // ARRANGE
    render(
      <DataTableSystemFilterHarness
        filterableSystemOptions={[createSystem({ name: 'Nintendo 64', nameShort: 'N64' })]}
        includeDefaultOption={true} // !!
        isSingleSelect={true} // !!
        defaultOptionValue="all" // !!
        defaultOptionLabel={'All systems' as TranslatedString}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /system/i }));

    // ASSERT
    const supportedOption = screen.getByRole('option', { name: /only supported systems/i });
    const allOption = screen.getByRole('option', { name: /all systems/i });

    expect(supportedOption).toBeVisible();
    expect(allOption).toBeVisible();

    // ... the "All systems" option should be marked as the default ...
    expect(within(allOption).getByRole('img', { hidden: true })).toBeVisible();
    expect(within(supportedOption).queryByRole('img', { hidden: true })).not.toBeInTheDocument();
  });

  it('given includeDefaultOption is true and isSingleSelect is false, shows only the primary default option', async () => {
    // ARRANGE
    render(
      <DataTableSystemFilterHarness
        filterableSystemOptions={[createSystem({ name: 'Nintendo 64', nameShort: 'N64' })]}
        includeDefaultOption={true} // !!
        isSingleSelect={false} // !!
        defaultOptionValue="supported"
        defaultOptionLabel={'Only supported systems' as TranslatedString}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /system/i }));

    // ASSERT
    expect(screen.getByRole('option', { name: /only supported systems/i })).toBeVisible();

    // ... should NOT show "All systems" in multi-select mode ...
    expect(screen.queryByRole('option', { name: /all systems/i })).not.toBeInTheDocument();
  });

  it('given includeDefaultOption is false, does not add default options', async () => {
    // ARRANGE
    render(
      <DataTableSystemFilterHarness
        filterableSystemOptions={[createSystem({ name: 'Nintendo 64', nameShort: 'N64' })]}
        includeDefaultOption={false} // !!
        defaultOptionLabel={'Only supported systems' as TranslatedString}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /system/i }));

    // ASSERT
    expect(
      screen.queryByRole('option', { name: /only supported systems/i }),
    ).not.toBeInTheDocument();
    expect(screen.queryByRole('option', { name: /all systems/i })).not.toBeInTheDocument();
  });

  it('given the user clicks a system option, selects it correctly', async () => {
    // ARRANGE
    render(
      <DataTableSystemFilterHarness
        filterableSystemOptions={[
          createSystem({ name: 'Nintendo 64', nameShort: 'N64' }),
          createSystem({ name: 'NES/Famicom', nameShort: 'NES' }),
        ]}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /system/i }));
    await userEvent.click(screen.getByRole('option', { name: /nintendo 64/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByTestId('filter-selected-label')).toBeVisible();
    });

    expect(screen.getByTestId('filter-selected-label')).toHaveTextContent('N64');
  });
});
