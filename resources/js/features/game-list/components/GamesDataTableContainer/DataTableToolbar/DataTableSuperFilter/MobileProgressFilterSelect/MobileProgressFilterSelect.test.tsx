import type { Column, Table } from '@tanstack/react-table';
import userEvent from '@testing-library/user-event';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';

import { MobileProgressFilterSelect } from './MobileProgressFilterSelect';

const mockColumn = {
  id: 'progress',
  getFacetedUniqueValues: () =>
    new Map([
      ['opt1', 1],
      ['opt2', 2],
    ]),
  getFilterValue: vi.fn().mockReturnValue([]),
  setFilterValue: vi.fn(),
} as unknown as Column<any, any>;

const createMockTable = (overrides = {}): Partial<Table<any>> => ({
  getColumn: vi.fn().mockReturnValue(mockColumn),
  ...overrides,
});

describe('Component: MobileProgressFilterSelect', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    window.HTMLElement.prototype.setPointerCapture = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <MobileProgressFilterSelect table={createMockTable() as Table<any>} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user prefers hardcore mode, shows hardcore specific options', async () => {
    // ARRANGE
    render(<MobileProgressFilterSelect table={createMockTable() as Table<any>} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({ playerPreferredMode: 'hardcore' }),
        },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('combobox'));

    // ASSERT
    expect(screen.getByText(/mastered only/i)).toBeVisible();
    expect(screen.queryByText(/completed only/i)).not.toBeInTheDocument();
  });

  it('given the user prefers softcore mode, shows softcore specific options', async () => {
    // ARRANGE
    render(<MobileProgressFilterSelect table={createMockTable() as Table<any>} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({ playerPreferredMode: 'softcore' }),
        },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('combobox'));

    // ASSERT
    expect(screen.getByText(/completed only/i)).toBeVisible();
    expect(screen.queryByText(/mastered only/i)).not.toBeInTheDocument();
  });

  it('given the user prefers mixed progress, shows mixed specific options', async () => {
    // ARRANGE
    render(<MobileProgressFilterSelect table={createMockTable() as Table<any>} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({ playerPreferredMode: 'mixed' }),
        },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('combobox'));

    // ASSERT
    expect(screen.getByText(/completed only/i)).toBeVisible();
    expect(screen.getByText(/mastered only/i)).toBeVisible();
  });

  it('given the user selects a filter value, calls setFilterValue correctly', async () => {
    // ARRANGE
    const setFilterValueSpy = vi.fn();
    const mockTable = createMockTable({
      getColumn: vi.fn().mockReturnValue({
        ...mockColumn,
        setFilterValue: setFilterValueSpy,
      }),
    });

    render(<MobileProgressFilterSelect table={mockTable as Table<any>} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({ playerPreferredMode: 'hardcore' }),
        },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.click(screen.getByRole('option', { name: /mastered only/i }));

    // ASSERT
    expect(setFilterValueSpy).toHaveBeenCalledWith(['eq_mastered']);
  });

  it('given the user selects "All Games", clears the filter value', async () => {
    // ARRANGE
    const setFilterValueSpy = vi.fn();
    const getFilterValueSpy = vi.fn().mockReturnValue(['eq_mastered']); // !! start with a value selected
    const mockTable = createMockTable({
      getColumn: vi.fn().mockReturnValue({
        ...mockColumn,
        setFilterValue: setFilterValueSpy,
        getFilterValue: getFilterValueSpy,
      }),
    });

    render(<MobileProgressFilterSelect table={mockTable as Table<any>} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({ playerPreferredMode: 'hardcore' }),
        },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('combobox'));
    await userEvent.click(screen.getByRole('option', { name: /all games/i }));

    // ASSERT
    expect(setFilterValueSpy).toHaveBeenCalledWith(undefined);
  });
});
