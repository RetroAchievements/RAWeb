import { renderHook } from '@/test';

import { useHubPageMetaDescription } from './useHubPageMetaDescription';

describe('Hook: useHubPageMetaDescription', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useHubPageMetaDescription(), {
      pageProps: {
        hub: { title: 'Test Hub' },
        relatedHubs: [],
        paginatedGameListEntries: { total: 0 },
      },
    });

    // ASSERT
    expect(result.current).toBeTruthy();
  });

  it('given the user is on the /hubs route, returns the main hubs description', () => {
    // ARRANGE
    const { result } = renderHook(() => useHubPageMetaDescription(), {
      url: '/hubs',
      pageProps: {
        hub: { title: 'Test Hub' },
        relatedHubs: [],
        paginatedGameListEntries: { total: 0 },
      },
    });

    // ASSERT
    expect(result.current).toMatch(/discover our extensive collection of retro game hubs/i);
  });

  it('given the hub has related hubs but no games, returns a description about related content', () => {
    // ARRANGE
    const { result } = renderHook(() => useHubPageMetaDescription(), {
      url: '/hubs/some-hub',
      pageProps: {
        hub: { title: 'Test Hub' },
        relatedHubs: [{ id: 1 }],
        paginatedGameListEntries: { total: 0 },
      },
    });

    // ASSERT
    expect(result.current).toMatch(/discover a curated collection of test hub related content/i);
  });

  it('given the hub has games, returns a description with the game count', () => {
    // ARRANGE
    const { result } = renderHook(() => useHubPageMetaDescription(), {
      url: '/hubs/some-hub',
      pageProps: {
        hub: { title: 'Test Hub' },
        relatedHubs: [],
        paginatedGameListEntries: { total: 100 },
      },
    });

    // ASSERT
    expect(result.current).toMatch(/explore a collection of 100 classic games/i);
  });

  it('given the hub has exactly one game, uses singular form in description', () => {
    // ARRANGE
    const { result } = renderHook(() => useHubPageMetaDescription(), {
      url: '/hubs/some-hub',
      pageProps: {
        hub: { title: 'Test Hub' },
        relatedHubs: [],
        paginatedGameListEntries: { total: 1 },
      },
    });

    // ASSERT
    expect(result.current).toMatch(/explore a collection of 1 classic game/i);
  });

  it('given the hub is orphaned, returns a basic hub description', () => {
    // ARRANGE
    const { result } = renderHook(() => useHubPageMetaDescription(), {
      url: '/hubs/some-hub',
      pageProps: {
        hub: { title: 'Test Hub' },
        relatedHubs: [],
        paginatedGameListEntries: { total: 0 },
      },
    });

    // ASSERT
    expect(result.current).toMatch(/explore the test hub hub/i);
  });
});
