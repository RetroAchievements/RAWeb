import type { Column, Table } from '@tanstack/react-table';
import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

import { MobileRequestsStatusFilterSelect } from './MobileRequestsStatusFilterSelect';

const mockColumn = {
  id: 'achievementsPublished',
  getFilterValue: vi.fn().mockReturnValue([]),
  setFilterValue: vi.fn(),
} as unknown as Column<any, any>;

const createMockTable = (overrides = {}): Partial<Table<any>> => ({
  getColumn: vi.fn().mockReturnValue(mockColumn),
  ...overrides,
});

describe('Component: MobileRequestsStatusFilterSelect', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    window.HTMLElement.prototype.setPointerCapture = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <MobileRequestsStatusFilterSelect table={createMockTable() as Table<any>} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the component renders, shows the requests filter options', async () => {
    // ARRANGE
    render(<MobileRequestsStatusFilterSelect table={createMockTable() as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('combobox'));

    // ASSERT
    expect(screen.getAllByText('Active')[0]).toBeVisible();
    expect(screen.getByText('All')).toBeVisible();
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

    render(<MobileRequestsStatusFilterSelect table={mockTable as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.click(screen.getByText('All'));

    // ASSERT
    expect(setFilterValueSpy).toHaveBeenCalledWith(['either']);
  });

  it('given there is an existing filter value "none", shows "Active" as the current value', () => {
    // ARRANGE
    const mockTable = createMockTable({
      getColumn: vi.fn().mockReturnValue({
        ...mockColumn,
        getFilterValue: vi.fn().mockReturnValue(['none']),
      }),
    });

    render(<MobileRequestsStatusFilterSelect table={mockTable as Table<any>} />);

    // ASSERT
    expect(screen.getByRole('combobox')).toHaveTextContent('Active');
  });

  it('given there is an existing filter value "either", shows "All" as the current value', () => {
    // ARRANGE
    const mockTable = createMockTable({
      getColumn: vi.fn().mockReturnValue({
        ...mockColumn,
        getFilterValue: vi.fn().mockReturnValue(['either']),
      }),
    });

    render(<MobileRequestsStatusFilterSelect table={mockTable as Table<any>} />);

    // ASSERT
    expect(screen.getByRole('combobox')).toHaveTextContent('All');
  });

  it('given no filter value is set, defaults to "none" (Active)', () => {
    // ARRANGE
    const mockTable = createMockTable({
      getColumn: vi.fn().mockReturnValue({
        ...mockColumn,
        getFilterValue: vi.fn().mockReturnValue(undefined),
      }),
    });

    render(<MobileRequestsStatusFilterSelect table={mockTable as Table<any>} />);

    // ASSERT
    expect(screen.getByRole('combobox')).toHaveTextContent('Active');
  });

  it('given the user selects "Active", sets the filter value to "none"', async () => {
    // ARRANGE
    const setFilterValueSpy = vi.fn();
    const mockTable = createMockTable({
      getColumn: vi.fn().mockReturnValue({
        ...mockColumn,
        getFilterValue: vi.fn().mockReturnValue(['either']),
        setFilterValue: setFilterValueSpy,
      }),
    });

    render(<MobileRequestsStatusFilterSelect table={mockTable as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.click(screen.getByText('Active'));

    // ASSERT
    expect(setFilterValueSpy).toHaveBeenCalledWith(['none']);
  });

  it('given the column is not found, renders without crashing', () => {
    // ARRANGE
    const mockTable = createMockTable({
      getColumn: vi.fn().mockReturnValue(undefined),
    });

    // ACT
    const { container } = render(
      <MobileRequestsStatusFilterSelect table={mockTable as Table<any>} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });
});
