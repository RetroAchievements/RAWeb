import type { Column, Table } from '@tanstack/react-table';
import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

import { GameTypeFilter } from './GameTypeFilter';

const mockColumn = {
  id: 'game-type',
  getFacetedUniqueValues: () =>
    new Map([
      ['retail', 1],
      ['hack', 2],
      ['homebrew', 3],
      ['prototype', 4],
      ['unlicensed', 5],
      ['demo', 6],
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

describe('Component: GameTypeFilter', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    window.HTMLElement.prototype.setPointerCapture = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GameTypeFilter table={createMockTable() as Table<any>} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no filters are selected, shows all game type options as unselected', async () => {
    // ARRANGE
    render(<GameTypeFilter table={createMockTable() as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /game type/i }));

    // ASSERT
    expect(screen.getByText(/all games/i)).toBeVisible();
    expect(screen.getByText(/retail/i)).toBeVisible();
    expect(screen.getByText(/hack/i)).toBeVisible();
    expect(screen.getByText(/homebrew/i)).toBeVisible();
    expect(screen.getByText(/prototype/i)).toBeVisible();
    expect(screen.getByText(/unlicensed/i)).toBeVisible();
    expect(screen.getByText(/demo/i)).toBeVisible();
  });

  it('given a game type is selected, updates the table filters correctly', async () => {
    // ARRANGE
    const setFiltersSpy = vi.fn();
    const mockTableWithSpy = createMockTable({
      setColumnFilters: setFiltersSpy,
    });

    render(<GameTypeFilter table={mockTableWithSpy as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /game type/i }));
    await userEvent.click(screen.getByText(/retail/i));

    // ASSERT
    expect(setFiltersSpy).toHaveBeenCalledWith(expect.any(Function));

    const updateFn = setFiltersSpy.mock.calls[0][0];
    const result = updateFn([{ id: 'otherFilter', value: 'someValue' }]);
    expect(result).toEqual([
      { id: 'otherFilter', value: 'someValue' },
      { id: 'game-type', value: ['retail'] },
    ]);
  });

  it('given there is an existing filter value, displays it as selected', async () => {
    // ARRANGE
    const mockTableWithFilter = createMockTable({
      getState: vi.fn().mockReturnValue({
        columnFilters: [{ id: 'game-type', value: ['homebrew'] }],
      }),
    });

    render(<GameTypeFilter table={mockTableWithFilter as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /game type/i }));

    // ASSERT
    expect(screen.getAllByText(/homebrew/i)[0]).toBeVisible();
  });

  it('given the user selects the "All Games" option, clears the filter value', async () => {
    // ARRANGE
    const setFiltersSpy = vi.fn();
    const mockTableWithSpy = createMockTable({
      getState: vi.fn().mockReturnValue({
        columnFilters: [{ id: 'game-type', value: ['retail'] }],
      }),
      setColumnFilters: setFiltersSpy,
    });

    render(<GameTypeFilter table={mockTableWithSpy as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /game type/i }));
    await userEvent.click(screen.getByText(/all games/i));

    // ASSERT
    expect(setFiltersSpy).toHaveBeenCalledWith(expect.any(Function));

    const updateFn = setFiltersSpy.mock.calls[0][0];
    const result = updateFn([{ id: 'otherFilter', value: 'someValue' }]);
    expect(result).toEqual([{ id: 'otherFilter', value: 'someValue' }]);
  });
});
