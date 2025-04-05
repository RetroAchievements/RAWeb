import type { Column, Table } from '@tanstack/react-table';
import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

import { MobileHasAchievementsFilterSelect } from './MobileHasAchievementsFilterSelect';

const mockColumn = {
  id: 'achievementsPublished',
  getFilterValue: vi.fn().mockReturnValue([]),
  setFilterValue: vi.fn(),
} as unknown as Column<any, any>;

const createMockTable = (overrides = {}): Partial<Table<any>> => ({
  getColumn: vi.fn().mockReturnValue(mockColumn),
  ...overrides,
});

describe('Component: MobileHasAchievementsFilterSelect', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    window.HTMLElement.prototype.setPointerCapture = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <MobileHasAchievementsFilterSelect table={createMockTable() as Table<any>} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the component renders, shows the achievement filter options', async () => {
    // ARRANGE
    render(<MobileHasAchievementsFilterSelect table={createMockTable() as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('combobox'));

    // ASSERT
    expect(screen.getByTestId('has-option')).toBeVisible();
    expect(screen.getByTestId('none-option')).toBeVisible();
    expect(screen.getByTestId('either-option')).toBeVisible();
  });

  it('given the user selects a filter value, calls setFilterValue with the correct value', async () => {
    // ARRANGE
    const setFilterValueSpy = vi.fn();
    const mockTable = createMockTable({
      getColumn: vi.fn().mockReturnValue({
        ...mockColumn,
        setFilterValue: setFilterValueSpy,
      }),
    });

    render(<MobileHasAchievementsFilterSelect table={mockTable as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.click(screen.getByTestId('has-option'));

    // ASSERT
    expect(setFilterValueSpy).toHaveBeenCalledWith(['has']);
  });

  it('given there is an existing filter value, shows it as the current value', () => {
    // ARRANGE
    const mockTable = createMockTable({
      getColumn: vi.fn().mockReturnValue({
        ...mockColumn,
        getFilterValue: vi.fn().mockReturnValue(['has']),
      }),
    });

    render(<MobileHasAchievementsFilterSelect table={mockTable as Table<any>} />);

    // ASSERT
    expect(screen.getByRole('combobox')).toHaveTextContent(/yes/i);
  });

  it('given the user selects "Both", sets the filter value to "either"', async () => {
    // ARRANGE
    const setFilterValueSpy = vi.fn();
    const mockTable = createMockTable({
      getColumn: vi.fn().mockReturnValue({
        ...mockColumn,
        setFilterValue: setFilterValueSpy,
      }),
    });

    render(<MobileHasAchievementsFilterSelect table={mockTable as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.click(screen.getByTestId('either-option'));

    // ASSERT
    expect(setFilterValueSpy).toHaveBeenCalledWith(['either']);
  });

  it('given the column is not found, renders without crashing', () => {
    // ARRANGE
    const mockTable = createMockTable({
      getColumn: vi.fn().mockReturnValue(undefined),
    });

    // ACT
    const { container } = render(
      <MobileHasAchievementsFilterSelect table={mockTable as Table<any>} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });
});
