import type { Column, Table } from '@tanstack/react-table';
import userEvent from '@testing-library/user-event';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';

import { DataTableProgressFilter } from './DataTableProgressFilter';

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

const createMockTable = (overrides = {}): Partial<Table<any>> => ({
  getColumn: vi.fn().mockReturnValue(mockColumn),
  ...overrides,
});

describe('Component: DataTableProgressFilter', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
    window.HTMLElement.prototype.setPointerCapture = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <DataTableProgressFilter table={createMockTable() as Table<any>} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user is a guest, renders as disabled', () => {
    // ARRANGE
    render(<DataTableProgressFilter table={createMockTable() as Table<any>} />, {
      pageProps: { auth: null },
    });

    // ASSERT
    expect(screen.getByRole('button')).toBeDisabled();
  });

  it('given the user prefers hardcore mode, shows hardcore specific options', async () => {
    // ARRANGE
    render(<DataTableProgressFilter table={createMockTable() as Table<any>} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({ playerPreferredMode: 'hardcore' }),
        },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /progress/i }));

    // ASSERT
    expect(screen.getByText(/mastered only/i)).toBeVisible();
    expect(screen.queryByText(/completed only/i)).not.toBeInTheDocument();
  });

  it('given the user prefers softcore mode, shows softcore specific options', async () => {
    // ARRANGE
    render(<DataTableProgressFilter table={createMockTable() as Table<any>} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({ playerPreferredMode: 'softcore' }),
        },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /progress/i }));

    // ASSERT
    expect(screen.getByText(/completed only/i)).toBeVisible();
    expect(screen.queryByText(/mastered only/i)).not.toBeInTheDocument();
  });

  it('given the user prefers mixed progress, shows mixed specific options', async () => {
    // ARRANGE
    render(<DataTableProgressFilter table={createMockTable() as Table<any>} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({ playerPreferredMode: 'mixed' }),
        },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /progress/i }));

    // ASSERT
    expect(screen.getByText(/completed only/i)).toBeVisible();
    expect(screen.getByText(/mastered only/i)).toBeVisible();
  });
});
