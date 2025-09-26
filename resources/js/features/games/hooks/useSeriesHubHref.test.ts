import * as ReactUse from 'react-use';

import { renderHook } from '@/test';

import { useSeriesHubHref } from './useSeriesHubHref';

describe('Hook: useSeriesHubHref', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useSeriesHubHref(123));

    // ASSERT
    expect(result.current.href).toBeTruthy();
  });

  it('given no preferences cookie exists, returns the href with default params', () => {
    // ARRANGE
    vi.spyOn(ReactUse, 'useCookie').mockReturnValue([null, vi.fn(), vi.fn()]);

    // ACT
    const { result } = renderHook(() => useSeriesHubHref(123));

    // ASSERT
    expect(result.current.href).toEqual([
      'hub.show',
      {
        gameSet: 123,
        sort: '-playersTotal',
        'filter[subsets]': 'only-games',
      },
    ]);
  });

  it('given a preferences cookie exists, returns the href without params after the effect runs', async () => {
    // ARRANGE
    vi.spyOn(ReactUse, 'useCookie').mockReturnValue(['some-preference-value', vi.fn(), vi.fn()]);

    // ACT
    const { result } = renderHook(() => useSeriesHubHref(123));

    // ASSERT
    expect(result.current.href).toEqual(['hub.show', { gameSet: 123 }]);
  });
});
