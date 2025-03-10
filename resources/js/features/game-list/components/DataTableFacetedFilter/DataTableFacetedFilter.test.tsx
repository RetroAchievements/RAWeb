import type { Column } from '@tanstack/react-table';
import userEvent from '@testing-library/user-event';
import { LuX } from 'react-icons/lu';

import { render, screen, within } from '@/test';
import type { TranslatedString } from '@/types/i18next';

import { DataTableFacetedFilter } from './DataTableFacetedFilter';

// Suppress "[Table] Column with id 'progress' does not exist".
console.error = vi.fn();

const mockOptions = [
  { t_label: 'Option 1' as TranslatedString, value: 'opt1' },
  { t_label: 'Option 2' as TranslatedString, value: 'opt2' },
  { t_label: 'Option 3' as TranslatedString, value: 'opt3' },
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
      <DataTableFacetedFilter
        options={mockOptions}
        column={mockColumn}
        t_title={'Test Filter' as TranslatedString}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the component is using the base variant, shows a button with the filter title', () => {
    // ARRANGE
    render(
      <DataTableFacetedFilter
        options={mockOptions}
        column={mockColumn}
        t_title={'Test Filter' as TranslatedString}
      />,
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
        t_title={'Test Filter' as TranslatedString}
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
      <DataTableFacetedFilter
        options={mockOptions}
        column={mockColumn}
        t_title={'Test Filter' as TranslatedString}
      />,
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
        t_title={'Test Filter' as TranslatedString}
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
      <DataTableFacetedFilter
        options={mockOptions}
        column={customColumn}
        t_title={'Test Filter' as TranslatedString}
      />,
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
      <DataTableFacetedFilter
        options={mockOptions}
        column={customColumn}
        t_title={'Test Filter' as TranslatedString}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /test filter/i }));

    // ASSERT
    expect(screen.getByText(/clear filters/i)).toBeVisible();
  });

  it('given isSearchable is false, does not show the search input', () => {
    // ARRANGE
    render(
      <DataTableFacetedFilter
        options={mockOptions}
        column={mockColumn}
        t_title={'Test Filter' as TranslatedString}
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
      <DataTableFacetedFilter
        options={mockOptions}
        column={customColumn}
        t_title={'Test Filter' as TranslatedString}
      />,
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
      { t_label: 'Option 1' as TranslatedString, value: 'opt1', icon: LuX },
      { t_label: 'Option 2' as TranslatedString, value: 'opt2' },
      { t_label: 'Option 3' as TranslatedString, value: 'opt3' },
    ];

    render(
      <DataTableFacetedFilter
        options={mockOptionsWithIcon}
        column={mockColumn}
        t_title={'Test Filter' as TranslatedString}
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
      <DataTableFacetedFilter
        options={mockOptions}
        column={customColumn}
        t_title={'Test Filter' as TranslatedString}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /test filter/i }));
    await userEvent.click(screen.getByText(/clear filters/i));

    // ASSERT
    expect(setFilterValueSpy).toHaveBeenCalledWith(undefined);
  });

  it('given a default option is selected in single select mode, clears the filter', async () => {
    // ARRANGE
    const setFilterValueSpy = vi.fn();
    const customColumn = {
      ...mockColumn,
      setFilterValue: setFilterValueSpy,
    } as unknown as Column<any, any>;

    const optionsWithDefault = [
      { t_label: 'All' as TranslatedString, value: 'all', isDefaultOption: true },
      ...mockOptions,
    ];

    render(
      <DataTableFacetedFilter
        options={optionsWithDefault}
        column={customColumn}
        t_title={'Test Filter' as TranslatedString}
        isSingleSelect={true}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /test filter/i }));
    await userEvent.click(screen.getByText(/all/i));

    // ASSERT
    expect(setFilterValueSpy).toHaveBeenCalledWith(undefined);
  });

  it('given options are organized in groups, renders the groups with headings', async () => {
    // ARRANGE
    const groupedOptions = [
      {
        t_heading: 'Group 1' as TranslatedString,
        options: [
          { t_label: 'Option 1' as TranslatedString, value: 'opt1' },
          { t_label: 'Option 2' as TranslatedString, value: 'opt2' },
        ],
      },
      {
        t_heading: 'Group 2' as TranslatedString,
        options: [
          { t_label: 'Option 3' as TranslatedString, value: 'opt3' },
          { t_label: 'Option 4' as TranslatedString, value: 'opt4' },
        ],
      },
    ];

    render(
      <DataTableFacetedFilter
        options={groupedOptions}
        column={mockColumn}
        t_title={'Test Filter' as TranslatedString}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /test filter/i }));

    // ASSERT
    expect(screen.getByText(/group 1/i)).toBeVisible();
    expect(screen.getByText(/group 2/i)).toBeVisible();
    expect(screen.getByText(/option 1/i)).toBeVisible();
    expect(screen.getByText(/option 4/i)).toBeVisible();
  });

  it('given an option has a description, shows the description', async () => {
    // ARRANGE
    const optionsWithDescriptions = [
      {
        t_label: 'Option 4' as TranslatedString,
        value: 'opt4',
        t_description: 'Description 4' as TranslatedString,
      },
      ...mockOptions,
    ];

    render(
      <DataTableFacetedFilter
        options={optionsWithDescriptions}
        column={mockColumn}
        t_title={'Test Filter' as TranslatedString}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /test filter/i }));

    // ASSERT
    expect(screen.getByText(/description 4/i)).toBeVisible();
  });

  it('given grouped options with a default option, shows default as selected when no values are selected', async () => {
    // ARRANGE
    const customColumn = {
      ...mockColumn,
      getFilterValue: () => [], // !! empty selection, so selectedValues.size will be 0.
    } as unknown as Column<any, any>;

    const groupedOptions = [
      {
        t_heading: 'Group 1' as TranslatedString,
        options: [
          {
            t_label: 'Default Option' as TranslatedString,
            value: 'default',
            isDefaultOption: true,
          },
          { t_label: 'Option 2' as TranslatedString, value: 'opt2' },
        ],
      },
      {
        t_heading: 'Group 2' as TranslatedString,
        options: [{ t_label: 'Option 3' as TranslatedString, value: 'opt3', icon: LuX }],
      },
    ];

    render(
      <DataTableFacetedFilter
        options={groupedOptions}
        column={customColumn}
        t_title={'Test Filter' as TranslatedString}
        isSingleSelect={true}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /test filter/i }));

    // ASSERT
    const defaultOption = screen.getByRole('option', { name: /default option/i });

    expect(within(defaultOption).getByRole('img', { hidden: true })).toBeVisible();
    expect(
      within(screen.getByRole('option', { name: /option 2/i })).queryByRole('img', {
        hidden: true,
      }),
    ).not.toBeInTheDocument();
  });

  it('given grouped options, toggles selection when an option is clicked', async () => {
    // ARRANGE
    const setFilterValueSpy = vi.fn();
    const customColumn = {
      ...mockColumn,
      setFilterValue: setFilterValueSpy,
    } as unknown as Column<any, any>;

    const groupedOptions = [
      {
        t_heading: 'Group 1' as TranslatedString,
        options: [
          { t_label: 'Option 1' as TranslatedString, value: 'opt1' },
          { t_label: 'Option 2' as TranslatedString, value: 'opt2' },
        ],
      },
    ];

    render(
      <DataTableFacetedFilter
        options={groupedOptions}
        column={customColumn}
        t_title={'Test Filter' as TranslatedString}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /test filter/i }));
    await userEvent.click(screen.getByText(/option 1/i));

    // ASSERT
    expect(setFilterValueSpy).toHaveBeenCalledWith(['opt1']);
  });
});
