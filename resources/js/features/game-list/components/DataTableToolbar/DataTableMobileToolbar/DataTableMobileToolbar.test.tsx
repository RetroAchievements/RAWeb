import { render, screen } from '@/test';
import { createZiggyProps } from '@/test/factories';

import { useGameListInfiniteQuery } from '../../../hooks/useGameListInfiniteQuery';
import DataTableMobileToolbar from './DataTableMobileToolbar';

vi.mock('../../../hooks/useGameListInfiniteQuery');

describe('Component: DataTableMobileToolbar', () => {
  beforeEach(() => {
    vi.mocked(useGameListInfiniteQuery).mockReturnValue({
      data: {
        pages: [{ total: 0 }],
      },
      isPending: false,
    } as any);
  });

  it('renders without crashing', () => {
    // ARRANGE
    const mockTable = {
      getState: () => ({
        columnFilters: [],
        pagination: {},
        sorting: [],
      }),
      getColumn: vi.fn(),
      getAllColumns: () => [],
    };

    const { container } = render(
      <DataTableMobileToolbar
        table={mockTable as any}
        randomGameApiRouteName="api.game.random"
        tableApiRouteName="api.game.index"
      />,
      {
        pageProps: { ziggy: createZiggyProps({ device: 'mobile' }) },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the game list query is loading, shows a skeleton loader', () => {
    // ARRANGE
    const mockTable = {
      getState: () => ({
        columnFilters: [],
        pagination: {},
        sorting: [],
      }),
      getColumn: vi.fn(),
      getAllColumns: () => [],
    };

    vi.mocked(useGameListInfiniteQuery).mockReturnValue({
      isPending: true,
      data: undefined,
    } as any);

    render(
      <DataTableMobileToolbar
        table={mockTable as any}
        randomGameApiRouteName="api.game.random"
        tableApiRouteName="api.game.index"
      />,
      {
        pageProps: { ziggy: createZiggyProps({ device: 'mobile' }) },
      },
    );

    // ASSERT
    expect(screen.getByTestId('skeleton')).toBeVisible();
  });

  it('given the query returns data, shows the total games count', () => {
    // ARRANGE
    const mockTable = {
      getState: () => ({
        columnFilters: [],
        pagination: {},
        sorting: [],
      }),
      getColumn: vi.fn(),
      getAllColumns: () => [],
    };

    vi.mocked(useGameListInfiniteQuery).mockReturnValue({
      isPending: false,
      data: {
        pages: [{ total: 42 }],
      },
    } as any);

    render(
      <DataTableMobileToolbar
        table={mockTable as any}
        randomGameApiRouteName="api.game.random"
        tableApiRouteName="api.game.index"
      />,
      {
        pageProps: { ziggy: createZiggyProps({ device: 'mobile' }) },
      },
    );

    // ASSERT
    expect(screen.getByText(/42 games/i)).toBeVisible();
  });
});
