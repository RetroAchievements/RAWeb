import type { Column, Table } from '@tanstack/react-table';
import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

import { MobileClaimedFilterSelect } from './MobileClaimedFilterSelect';

const mockColumn = {
  id: 'hasActiveOrInReviewClaims',
  getFilterValue: vi.fn().mockReturnValue(undefined),
  setFilterValue: vi.fn(),
} as unknown as Column<any, any>;

const createMockTable = (overrides = {}): Partial<Table<any>> => ({
  getColumn: vi.fn().mockReturnValue(mockColumn),
  ...overrides,
});

describe('Component: MobileClaimedFilterSelect', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    window.HTMLElement.prototype.setPointerCapture = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <MobileClaimedFilterSelect table={createMockTable() as Table<any>} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('shows all claimed filter options', async () => {
    // ARRANGE
    render(<MobileClaimedFilterSelect table={createMockTable() as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('combobox'));

    // ASSERT
    expect(screen.getByRole('option', { name: /any/i })).toBeVisible();
    expect(screen.getByRole('option', { name: 'Claimed' })).toBeVisible();
    expect(screen.getByRole('option', { name: /unclaimed/i })).toBeVisible();
  });

  it('given no filter value is set, defaults to "any"', () => {
    // ARRANGE
    render(<MobileClaimedFilterSelect table={createMockTable() as Table<any>} />);

    // ASSERT
    expect(screen.getByRole('combobox')).toHaveTextContent(/any/i);
  });

  it('given the user selects "Claimed", calls setFilterValue with ["claimed"]', async () => {
    // ARRANGE
    const setFilterValueSpy = vi.fn();
    const mockTable = createMockTable({
      getColumn: vi.fn().mockReturnValue({
        ...mockColumn,
        setFilterValue: setFilterValueSpy,
      }),
    });

    render(<MobileClaimedFilterSelect table={mockTable as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.click(screen.getByRole('option', { name: 'Claimed' }));

    // ASSERT
    expect(setFilterValueSpy).toHaveBeenCalledWith(['claimed']);
  });

  it('given the user selects "Unclaimed", calls setFilterValue with ["unclaimed"]', async () => {
    // ARRANGE
    const setFilterValueSpy = vi.fn();
    const mockTable = createMockTable({
      getColumn: vi.fn().mockReturnValue({
        ...mockColumn,
        setFilterValue: setFilterValueSpy,
      }),
    });

    render(<MobileClaimedFilterSelect table={mockTable as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.click(screen.getByText(/unclaimed/i));

    // ASSERT
    expect(setFilterValueSpy).toHaveBeenCalledWith(['unclaimed']);
  });

  it('given the user selects "Any", calls setFilterValue with ["any"]', async () => {
    // ARRANGE
    const setFilterValueSpy = vi.fn();
    const mockTable = createMockTable({
      getColumn: vi.fn().mockReturnValue({
        ...mockColumn,
        getFilterValue: vi.fn().mockReturnValue(['claimed']),
        setFilterValue: setFilterValueSpy,
      }),
    });

    render(<MobileClaimedFilterSelect table={mockTable as Table<any>} />);

    // ACT
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.click(screen.getByRole('option', { name: /any/i }));

    // ASSERT
    expect(setFilterValueSpy).toHaveBeenCalledWith(['any']);
  });

  it('given a filter value is already set, displays it as the current value', () => {
    // ARRANGE
    const mockTable = createMockTable({
      getColumn: vi.fn().mockReturnValue({
        ...mockColumn,
        getFilterValue: vi.fn().mockReturnValue(['claimed']),
      }),
    });

    render(<MobileClaimedFilterSelect table={mockTable as Table<any>} />);

    // ASSERT
    expect(screen.getByRole('combobox')).toHaveTextContent(/claimed/i);
  });

  it('given the column is not found, renders without crashing', () => {
    // ARRANGE
    const mockTable = createMockTable({
      getColumn: vi.fn().mockReturnValue(undefined),
    });

    const { container } = render(<MobileClaimedFilterSelect table={mockTable as Table<any>} />);

    // ASSERT
    expect(container).toBeTruthy();
  });
});
