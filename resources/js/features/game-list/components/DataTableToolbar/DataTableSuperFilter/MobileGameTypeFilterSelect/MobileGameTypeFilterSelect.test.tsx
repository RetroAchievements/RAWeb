import type { Column, Table } from '@tanstack/react-table';
import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

import { MobileGameTypeFilterSelect } from './MobileGameTypeFilterSelect';

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

describe('Component: MobileGameTypeFilterSelect', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    window.HTMLElement.prototype.setPointerCapture = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <MobileGameTypeFilterSelect table={createMockTable() as Table<any>} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the component renders, shows all available game type options', async () => {
    // ARRANGE
    render(<MobileGameTypeFilterSelect table={createMockTable() as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('combobox'));

    // ASSERT
    expect(screen.getByTestId('retail-option')).toBeVisible();
    expect(screen.getByTestId('hack-option')).toBeVisible();
    expect(screen.getByTestId('homebrew-option')).toBeVisible();
    expect(screen.getByTestId('prototype-option')).toBeVisible();
    expect(screen.getByTestId('unlicensed-option')).toBeVisible();
    expect(screen.getByTestId('demo-option')).toBeVisible();
  });

  it('given the user selects a game type, updates the filters correctly', async () => {
    // ARRANGE
    const setColumnFiltersSpy = vi.fn();
    const mockTable = createMockTable({
      setColumnFilters: setColumnFiltersSpy,
    });

    render(<MobileGameTypeFilterSelect table={mockTable as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.click(screen.getByTestId('retail-option'));

    // ASSERT
    expect(setColumnFiltersSpy).toHaveBeenCalledWith(expect.any(Function));

    const updateFn = setColumnFiltersSpy.mock.calls[0][0];
    const result = updateFn([]);
    expect(result).toEqual([{ id: 'game-type', value: ['retail'] }]);
  });

  it('given other column filters exist, preserves them when selecting a game type', async () => {
    // ARRANGE
    const setColumnFiltersSpy = vi.fn();
    const mockTable = createMockTable({
      getState: vi.fn().mockReturnValue({
        columnFilters: [{ id: 'otherFilter', value: 'someValue' }],
      }),
      setColumnFilters: setColumnFiltersSpy,
    });

    render(<MobileGameTypeFilterSelect table={mockTable as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.click(screen.getByTestId('hack-option'));

    // ASSERT
    expect(setColumnFiltersSpy).toHaveBeenCalledWith(expect.any(Function));

    const updateFn = setColumnFiltersSpy.mock.calls[0][0];
    const result = updateFn([{ id: 'otherFilter', value: 'someValue' }]);
    expect(result).toEqual([
      { id: 'otherFilter', value: 'someValue' },
      { id: 'game-type', value: ['hack'] },
    ]);
  });

  it('given a game type is selected, displays that value in the select', () => {
    // ARRANGE
    const mockTable = createMockTable({
      getState: vi.fn().mockReturnValue({
        columnFilters: [{ id: 'game-type', value: ['homebrew'] }],
      }),
    });

    render(<MobileGameTypeFilterSelect table={mockTable as Table<any>} />);

    // ASSERT
    expect(screen.getByRole('combobox')).toHaveTextContent(/homebrew/i);
  });
});
