import type { Column, Table } from '@tanstack/react-table';
import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

import { SetTypeFilter } from './SetTypeFilter';

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

describe('Component: SetTypeFilter', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    window.HTMLElement.prototype.setPointerCapture = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<SetTypeFilter table={createMockTable() as Table<any>} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no filters are selected, shows all options as unselected', async () => {
    // ARRANGE
    render(<SetTypeFilter table={createMockTable() as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /set type/i }));

    // ASSERT
    expect(screen.getByText(/all sets/i)).toBeVisible();
    expect(screen.getByText(/main sets only/i)).toBeVisible();
    expect(screen.getByText(/subsets only/i)).toBeVisible();
  });

  it('given a filter option is selected, updates the table filters correctly', async () => {
    // ARRANGE
    const setFiltersSpy = vi.fn();
    const mockTableWithSpy = createMockTable({
      setColumnFilters: setFiltersSpy,
    });

    render(<SetTypeFilter table={mockTableWithSpy as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /set type/i }));
    await userEvent.click(screen.getByText(/main sets only/i));

    // ASSERT
    expect(setFiltersSpy).toHaveBeenCalledWith(expect.any(Function));
  });

  it('given there is an existing filter value, displays it as selected', async () => {
    // ARRANGE
    const mockTableWithFilter = createMockTable({
      getState: vi.fn().mockReturnValue({
        columnFilters: [{ id: 'subsets', value: 'only-games' }],
      }),
    });

    render(<SetTypeFilter table={mockTableWithFilter as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /set type/i }));

    // ASSERT
    expect(screen.getByText(/main sets only/i)).toBeVisible();
  });

  it('given a filter option is selected, updates the table filters correctly', async () => {
    // ARRANGE
    const setFiltersSpy = vi.fn();
    const mockTableWithSpy = createMockTable({
      setColumnFilters: setFiltersSpy,
    });

    render(<SetTypeFilter table={mockTableWithSpy as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /set type/i }));
    await userEvent.click(screen.getByText(/main sets only/i));

    // ASSERT
    expect(setFiltersSpy).toHaveBeenCalledWith(expect.any(Function));

    const updateFn = setFiltersSpy.mock.calls[0][0];
    const result = updateFn([{ id: 'otherFilter', value: 'someValue' }]);
    expect(result).toEqual([
      { id: 'otherFilter', value: 'someValue' },
      { id: 'subsets', value: ['only-games'] },
    ]);
  });

  it('given the "All Sets" option is selected, removes the filter from filter state', async () => {
    // ARRANGE
    const setFiltersSpy = vi.fn();
    const mockTableWithSpy = createMockTable({
      setColumnFilters: setFiltersSpy,
    });

    render(<SetTypeFilter table={mockTableWithSpy as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /set type/i }));

    await userEvent.click(screen.getByText(/main sets only/i));
    await userEvent.click(screen.getByText(/all sets/i));

    // ASSERT
    const updateFn = setFiltersSpy.mock.calls[1][0]; // !! the 2nd call
    const result = updateFn([{ id: 'otherFilter', value: 'someValue' }]);
    expect(result).toEqual([{ id: 'otherFilter', value: 'someValue' }]);
  });
});
