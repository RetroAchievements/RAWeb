import type { Column, Table } from '@tanstack/react-table';
import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

import { MobileSetTypeFilterSelect } from './MobileSetTypeFilterSelect';

const mockColumn = {
  id: 'subsets',
  getFacetedUniqueValues: () =>
    new Map([
      ['both', 1],
      ['only-games', 2],
      ['only-subsets', 3],
    ]),
  getFilterValue: vi.fn().mockReturnValue([]),
  setFilterValue: vi.fn(),
} as unknown as Column<any, any>;

const createMockTable = (overrides = {}): Partial<Table<any>> => ({
  getState: vi.fn().mockReturnValue({ columnFilters: [] }),
  setColumnFilters: vi.fn(),
  getColumn: vi.fn().mockReturnValue(mockColumn),
  ...overrides,
});

describe('Component: MobileSetTypeFilterSelect', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    window.HTMLElement.prototype.setPointerCapture = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <MobileSetTypeFilterSelect table={createMockTable() as Table<any>} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the component renders, shows the set type options', async () => {
    // ARRANGE
    render(<MobileSetTypeFilterSelect table={createMockTable() as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('combobox'));

    // ASSERT
    expect(screen.getByTestId('both-option')).toBeVisible();
    expect(screen.getByTestId('only-games-option')).toBeVisible();
    expect(screen.getByTestId('only-subsets-option')).toBeVisible();
  });

  it('given the user selects a filter value, calls setColumnFilters correctly', async () => {
    // ARRANGE
    const setColumnFiltersSpy = vi.fn();
    const mockTable = createMockTable({
      setColumnFilters: setColumnFiltersSpy,
    });

    render(<MobileSetTypeFilterSelect table={mockTable as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.click(screen.getByTestId('only-games-option'));

    // ASSERT
    expect(setColumnFiltersSpy).toHaveBeenCalledWith(expect.any(Function));
  });

  it('given the user selects a value, updates the filters while preserving other column filters', async () => {
    // ARRANGE
    const setColumnFiltersSpy = vi.fn();
    const mockTable = createMockTable({
      getState: vi.fn().mockReturnValue({
        columnFilters: [{ id: 'otherFilter', value: 'someValue' }],
      }),
      setColumnFilters: setColumnFiltersSpy,
    });

    render(<MobileSetTypeFilterSelect table={mockTable as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.click(screen.getByTestId('only-games-option'));

    // ASSERT
    expect(setColumnFiltersSpy).toHaveBeenCalledWith(expect.any(Function));

    const updateFn = setColumnFiltersSpy.mock.calls[0][0];
    const result = updateFn([{ id: 'otherFilter', value: 'someValue' }]);
    expect(result).toEqual([
      { id: 'otherFilter', value: 'someValue' },
      { id: 'subsets', value: ['only-games'] },
    ]);
  });

  it('given the user selects "All Sets", clears the filter value', async () => {
    // ARRANGE
    const setColumnFiltersSpy = vi.fn();
    const mockTable = createMockTable({
      getState: vi.fn().mockReturnValue({
        columnFilters: [{ id: 'subsets', value: 'only-games' }],
      }),
      setColumnFilters: setColumnFiltersSpy,
    });

    render(<MobileSetTypeFilterSelect table={mockTable as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.click(screen.getByTestId('both-option'));

    // ASSERT
    expect(setColumnFiltersSpy).toHaveBeenCalledWith(expect.any(Function));
  });
});
