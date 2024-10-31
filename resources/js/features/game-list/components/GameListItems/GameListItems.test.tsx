import axios from 'axios';
import {
  mockAllIsIntersecting,
  resetIntersectionMocking,
} from 'react-intersection-observer/test-utils';

import { render, screen } from '@/test';
import { createGameListEntry, createZiggyProps } from '@/test/factories';

import GameListItems from './GameListItems';

// Suppress AggregateError invocations from unmocked fetch calls to the back-end.
console.error = vi.fn();

describe('Component: GameListItems', () => {
  afterEach(() => {
    resetIntersectionMocking();
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
});
