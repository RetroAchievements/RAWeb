import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import {
  mockAllIsIntersecting,
  resetIntersectionMocking,
} from 'react-intersection-observer/test-utils';

import { render, screen, waitFor } from '@/test';
import { createGame, createGameListEntry, createZiggyProps } from '@/test/factories';

import GameListItems from './GameListItems';

// Suppress AggregateError invocations from unmocked fetch calls to the back-end.
console.error = vi.fn();

describe('Component: GameListItems', () => {
  let queryClient: QueryClient;

  beforeEach(() => {
    queryClient = new QueryClient({
      defaultOptions: { queries: { retry: false } },
    });
  });

  afterEach(() => {
    resetIntersectionMocking();
    vi.resetAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    mockAllIsIntersecting(false);

    const { container } = render(
      <GameListItems
        sorting={[{ id: 'title', desc: false }]}
        pagination={{ pageIndex: 0, pageSize: 100 }}
        columnFilters={[]}
      />,
      {
        pageProps: {
          ziggy: createZiggyProps({ device: 'mobile' }),
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no games, renders an empty state', async () => {
    // ARRANGE
    mockAllIsIntersecting(false);

    const data: App.Data.PaginatedData<App.Platform.Data.GameListEntry> = {
      currentPage: 1,
      lastPage: 1,
      perPage: 100,
      total: 0,
      unfilteredTotal: 0,
      items: [],
      links: { firstPageUrl: '#', lastPageUrl: '#', nextPageUrl: '#', previousPageUrl: '#' },
    };
    vi.spyOn(axios, 'get').mockResolvedValueOnce({ data });

    render(
      <GameListItems
        sorting={[{ id: 'title', desc: false }]}
        pagination={{ pageIndex: 0, pageSize: 100 }}
        columnFilters={[]}
      />,
      {
        pageProps: {
          ziggy: createZiggyProps({ device: 'mobile' }),
        },
      },
    );

    // ASSERT
    expect(await screen.findByText(/no games found/i)).toBeVisible();
  });

  it('displays game list items correctly', async () => {
    // ARRANGE
    mockAllIsIntersecting(false);

    const data: App.Data.PaginatedData<App.Platform.Data.GameListEntry> = {
      currentPage: 1,
      lastPage: 1,
      perPage: 100,
      total: 0,
      unfilteredTotal: 0,
      items: [
        createGameListEntry({ game: createGame({ title: 'Game 1' }) }),
        createGameListEntry({ game: createGame({ title: 'Game 2' }) }),
        createGameListEntry({ game: createGame({ title: 'Game 3' }) }),
      ],
      links: { firstPageUrl: '#', lastPageUrl: '#', nextPageUrl: '#', previousPageUrl: '#' },
    };
    vi.spyOn(axios, 'get').mockResolvedValueOnce({ data });

    render(
      <GameListItems
        sorting={[{ id: 'title', desc: false }]}
        pagination={{ pageIndex: 0, pageSize: 100 }}
        columnFilters={[]}
      />,
      {
        pageProps: {
          ziggy: createZiggyProps({ device: 'mobile' }),
        },
      },
    );

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/game 1/i)).toBeVisible();
    });

    expect(screen.getByText(/game 2/i)).toBeVisible();
    expect(screen.getByText(/game 3/i)).toBeVisible();
  });

  it('given the user is at the end of the list, displays a label telling them', async () => {
    // ARRANGE
    mockAllIsIntersecting(false);

    const data: App.Data.PaginatedData<App.Platform.Data.GameListEntry> = {
      currentPage: 1,
      lastPage: 1,
      perPage: 100,
      total: 1,
      unfilteredTotal: 1,
      items: [createGameListEntry()],
      links: { firstPageUrl: '#', lastPageUrl: '#', nextPageUrl: '#', previousPageUrl: '#' },
    };
    vi.spyOn(axios, 'get').mockResolvedValueOnce({ data });

    render(
      <GameListItems
        sorting={[{ id: 'title', desc: false }]}
        pagination={{ pageIndex: 0, pageSize: 100 }}
        columnFilters={[]}
      />,
      {
        pageProps: {
          ziggy: createZiggyProps({ device: 'mobile' }),
        },
      },
    );

    // ASSERT
    expect(await screen.findByText(/end of the list/i)).toBeVisible();
  });

  it('displays more content when the Load More button is pressed', async () => {
    // ARRANGE
    const firstPageGames = [
      createGameListEntry({ game: createGame({ title: 'Game 1' }) }),
      createGameListEntry({ game: createGame({ title: 'Game 2' }) }),
      createGameListEntry({ game: createGame({ title: 'Game 3' }) }),
    ];
    const secondPageGames = [
      createGameListEntry({ game: createGame({ title: 'Game 4' }) }),
      createGameListEntry({ game: createGame({ title: 'Game 5' }) }),
      createGameListEntry({ game: createGame({ title: 'Game 6' }) }),
    ];

    const firstPageData: App.Data.PaginatedData<App.Platform.Data.GameListEntry> = {
      currentPage: 1,
      lastPage: 2,
      perPage: 3,
      total: 6,
      unfilteredTotal: 6,
      items: firstPageGames,
      links: { firstPageUrl: '#', lastPageUrl: '#', nextPageUrl: '#', previousPageUrl: '#' },
    };

    const secondPageData: App.Data.PaginatedData<App.Platform.Data.GameListEntry> = {
      currentPage: 2,
      lastPage: 2,
      perPage: 3,
      total: 6,
      unfilteredTotal: 6,
      items: secondPageGames,
      links: { firstPageUrl: '#', lastPageUrl: '#', nextPageUrl: '#', previousPageUrl: '#' },
    };

    vi.spyOn(axios, 'get')
      .mockResolvedValueOnce({ data: firstPageData })
      .mockResolvedValue({ data: secondPageData });

    render(
      <QueryClientProvider client={queryClient}>
        <GameListItems
          sorting={[{ id: 'title', desc: false }]}
          pagination={{ pageIndex: 0, pageSize: 100 }}
          columnFilters={[]}
        />
      </QueryClientProvider>,
      {
        pageProps: {
          ziggy: createZiggyProps({ device: 'mobile' }),
        },
      },
    );

    // ACT
    // Wait for the initial load.
    await waitFor(() => {
      screen.getByText(/game 1/i);
    });

    // Click the Load More button.
    await userEvent.click(screen.getByRole('button', { name: /load more/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/game 4/i)).toBeVisible();
    });
  });

  it('fetches more data when scrolled to the bottom', async () => {
    // ARRANGE
    mockAllIsIntersecting(false);

    const firstPageGames = [
      createGameListEntry({ game: createGame({ title: 'Game 1' }) }),
      createGameListEntry({ game: createGame({ title: 'Game 2' }) }),
      createGameListEntry({ game: createGame({ title: 'Game 3' }) }),
    ];
    const secondPageGames = [
      createGameListEntry({ game: createGame({ title: 'Game 4' }) }),
      createGameListEntry({ game: createGame({ title: 'Game 5' }) }),
      createGameListEntry({ game: createGame({ title: 'Game 6' }) }),
    ];

    const firstPageData: App.Data.PaginatedData<App.Platform.Data.GameListEntry> = {
      currentPage: 1,
      lastPage: 2,
      perPage: 3,
      total: 6,
      unfilteredTotal: 6,
      items: firstPageGames,
      links: { firstPageUrl: '#', lastPageUrl: '#', nextPageUrl: '#', previousPageUrl: '#' },
    };

    const secondPageData: App.Data.PaginatedData<App.Platform.Data.GameListEntry> = {
      currentPage: 2,
      lastPage: 2,
      perPage: 3,
      total: 6,
      unfilteredTotal: 6,
      items: secondPageGames,
      links: { firstPageUrl: '#', lastPageUrl: '#', nextPageUrl: '#', previousPageUrl: '#' },
    };

    const getSpy = vi
      .spyOn(axios, 'get')
      .mockResolvedValueOnce({ data: firstPageData })
      .mockResolvedValue({ data: secondPageData });

    render(
      <QueryClientProvider client={queryClient}>
        <GameListItems
          sorting={[{ id: 'title', desc: false }]}
          pagination={{ pageIndex: 0, pageSize: 100 }}
          columnFilters={[]}
        />
      </QueryClientProvider>,
      {
        pageProps: {
          ziggy: createZiggyProps({ device: 'mobile' }),
        },
      },
    );

    // ACT
    // Wait for the initial load.
    await waitFor(() => {
      screen.getByText(/game 1/i);
    });

    // Simulate scrolling to the bottom.
    mockAllIsIntersecting(true);

    // ASSERT
    await waitFor(() => {
      expect(getSpy.mock.calls.length).toBeGreaterThanOrEqual(2);
    });

    await userEvent.click(screen.getByRole('button', { name: /load more/i }));

    expect(screen.getByText(/game 4/i)).toBeVisible();
  });
});
