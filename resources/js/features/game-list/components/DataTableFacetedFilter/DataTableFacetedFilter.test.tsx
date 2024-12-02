import type { Column } from '@tanstack/react-table';
import userEvent from '@testing-library/user-event';
import { LuX } from 'react-icons/lu';

import { render, screen, within } from '@/test';

import { DataTableFacetedFilter } from './DataTableFacetedFilter';

const mockOptions = [
  { label: 'Option 1', value: 'opt1' },
  { label: 'Option 2', value: 'opt2' },
  { label: 'Option 3', value: 'opt3' },
];

const mockColumn = {
  id: 'test-column',
  getFacetedUniqueValues: () =>
    new Map([
      ['opt1', 1],
      ['opt2', 2],
    ]),
  getFilterValue: vi.fn().mockReturnValue([]),
  setFilterValue: vi.fn(),
} as unknown as Column<any, any>;

describe('Component: DataTableFacetedFilter', () => {
  beforeEach(() => {
    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <DataTableFacetedFilter options={mockOptions} column={mockColumn} t_title="Test Filter" />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the component is using the base variant, shows a button with the filter title', () => {
    // ARRANGE
    render(
      <DataTableFacetedFilter options={mockOptions} column={mockColumn} t_title="Test Filter" />,
    );

    // ASSERT
    expect(screen.getByRole('button', { name: /test filter/i })).toBeVisible();
  });

  it('given the component is using the drawer variant, shows filter title as text instead of a button', () => {
    // ARRANGE
    render(
      <DataTableFacetedFilter
        options={mockOptions}
        column={mockColumn}
        t_title="Test Filter"
        variant="drawer"
      />,
    );

    // ASSERT
    expect(screen.getByText('Test Filter')).toBeVisible();
    expect(screen.queryByRole('button', { name: /test filter/i })).not.toBeInTheDocument();
  });

  it('given the user clicks the filter button, shows filter options in a popover', async () => {
    // ARRANGE
    render(
      <DataTableFacetedFilter options={mockOptions} column={mockColumn} t_title="Test Filter" />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /test filter/i }));

    // ASSERT
    expect(screen.getByText(/option 1/i)).toBeVisible();
    expect(screen.getByText(/option 2/i)).toBeVisible();
    expect(screen.getByText(/option 3/i)).toBeVisible();
  });

  it('given single select mode, selecting an option replaces the previous selection', async () => {
    // ARRANGE
    const setFilterValueSpy = vi.fn();
    const customColumn = {
      ...mockColumn,
      setFilterValue: setFilterValueSpy,
    } as unknown as Column<any, any>;

    render(
      <DataTableFacetedFilter
        options={mockOptions}
        column={customColumn}
        t_title="Test Filter"
        isSingleSelect={true}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /test filter/i }));
    await userEvent.click(screen.getByText(/option 1/i));
    await userEvent.click(screen.getByText(/option 2/i));

    // ASSERT
    expect(setFilterValueSpy).toHaveBeenCalledWith(['opt2']);
  });

  it('given multiselect mode, selecting options adds to the current selection', async () => {
    // ARRANGE
    const setFilterValueSpy = vi.fn();
    const customColumn = {
      ...mockColumn,
      setFilterValue: setFilterValueSpy,
    } as unknown as Column<any, any>;

    render(
      <DataTableFacetedFilter options={mockOptions} column={customColumn} t_title="Test Filter" />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /test filter/i }));
    await userEvent.click(screen.getByText(/option 1/i));
    await userEvent.click(screen.getByText(/option 2/i));

    // ASSERT
    expect(setFilterValueSpy).toHaveBeenCalledWith(['opt1', 'opt2']);
  });

  it('given selected filters exist, shows a clear filters button', async () => {
    // ARRANGE
    const customColumn = {
      ...mockColumn,
      getFilterValue: () => ['opt1', 'opt2'],
    } as unknown as Column<any, any>;

    render(
      <DataTableFacetedFilter options={mockOptions} column={customColumn} t_title="Test Filter" />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /test filter/i }));

    // ASSERT
    expect(screen.getByText(/clear filters/i)).toBeVisible();
  });

  it('given selected filters exist, shows a count badge on the filter button', () => {
    // ARRANGE
    const customColumn = {
      ...mockColumn,
      getFilterValue: () => ['opt1', 'opt2'],
    } as unknown as Column<any, any>;

    render(
      <DataTableFacetedFilter options={mockOptions} column={customColumn} t_title="Test Filter" />,
    );

    // ASSERT
    expect(screen.getByText('2')).toBeVisible();
  });

  it('given isSearchable is false, does not show the search input', () => {
    // ARRANGE
    render(
      <DataTableFacetedFilter
        options={mockOptions}
        column={mockColumn}
        t_title="Test Filter"
        isSearchable={false}
      />,
    );

    // ASSERT
    expect(screen.queryByPlaceholderText(/test filter/i)).not.toBeInTheDocument();
  });

  it('given an option is selected, clicking it again removes it from the current selection', async () => {
    // ARRANGE
    const setFilterValueSpy = vi.fn();
    const customColumn = {
      ...mockColumn,
      getFilterValue: () => ['opt1'],
      setFilterValue: setFilterValueSpy,
    } as unknown as Column<any, any>;

    render(
      <DataTableFacetedFilter options={mockOptions} column={customColumn} t_title="Test Filter" />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /test filter/i }));

    const popoverContent = screen.getByRole('listbox');
    const option = within(popoverContent).getByRole('option', { name: /option 1/i });
    await userEvent.click(option);

    // ASSERT
    expect(setFilterValueSpy).toHaveBeenCalledWith(undefined);
  });

  it('given options with icons, renders the icons', async () => {
    // ARRANGE
    const mockOptionsWithIcon = [
      { label: 'Option 1', value: 'opt1', icon: LuX },
      { label: 'Option 2', value: 'opt2' },
      { label: 'Option 3', value: 'opt3' },
    ];

    render(
      <DataTableFacetedFilter
        options={mockOptionsWithIcon}
        column={mockColumn}
        t_title="Test Filter"
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /test filter/i }));

    // ASSERT
    expect(screen.getByTestId('option-icon')).toBeVisible();
  });

  it('given user clicks the clear filters button, resets the filter value', async () => {
    // ARRANGE
    const setFilterValueSpy = vi.fn();
    const customColumn = {
      ...mockColumn,
      getFilterValue: () => ['opt1', 'opt2'],
      setFilterValue: setFilterValueSpy,
    } as unknown as Column<any, any>;

    render(
      <DataTableFacetedFilter options={mockOptions} column={customColumn} t_title="Test Filter" />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /test filter/i }));
    await userEvent.click(screen.getByText(/clear filters/i));

    // ASSERT
    expect(setFilterValueSpy).toHaveBeenCalledWith(undefined);
  });
});
