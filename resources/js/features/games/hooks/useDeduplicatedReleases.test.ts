import { renderHook } from '@/test';
import { createGameRelease } from '@/test/factories';

import { useDeduplicatedReleases } from './useDeduplicatedReleases';

describe('Hook: useDeduplicatedReleases', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const releases: App.Platform.Data.GameRelease[] = [];

    // ACT
    const { result } = renderHook(() => useDeduplicatedReleases(releases));

    // ASSERT
    expect(result.current).toEqual([]);
  });

  it('given releases with different regions and dates, returns all releases', () => {
    // ARRANGE
    const releases = [
      createGameRelease({ region: 'na', releasedAt: '2024-01-01T00:00:00Z' }),
      createGameRelease({ region: 'jp', releasedAt: '2024-01-01T00:00:00Z' }),
      createGameRelease({ region: 'na', releasedAt: '2024-02-01T00:00:00Z' }),
    ];

    // ACT
    const { result } = renderHook(() => useDeduplicatedReleases(releases));

    // ASSERT
    expect(result.current).toHaveLength(3);
    expect(result.current).toEqual(releases);
  });

  it('given releases with the same region and date, keeps only the first occurrence', () => {
    // ARRANGE
    const releases = [
      createGameRelease({ region: 'na', releasedAt: '2024-01-01T00:00:00Z', title: 'First' }),
      createGameRelease({ region: 'na', releasedAt: '2024-01-01T12:00:00Z', title: 'Second' }),
      createGameRelease({ region: 'na', releasedAt: '2024-01-01T23:59:59Z', title: 'Third' }),
    ];

    // ACT
    const { result } = renderHook(() => useDeduplicatedReleases(releases));

    // ASSERT
    expect(result.current).toHaveLength(1);
    expect(result.current[0].title).toEqual('First');
  });

  it('given releases with "worldwide" region, treats them as "WW"', () => {
    // ARRANGE
    const releases = [
      createGameRelease({ region: 'worldwide', releasedAt: '2024-01-01T00:00:00Z' }),
      createGameRelease({ region: 'other', releasedAt: '2024-01-01T00:00:00Z' }),
    ];

    // ACT
    const { result } = renderHook(() => useDeduplicatedReleases(releases));

    // ASSERT
    // ... both are treated as WW with the same date, so only one should remain ...
    expect(result.current).toHaveLength(1);
  });

  it('given releases with "other" region, treats them as "WW"', () => {
    // ARRANGE
    const releases = [
      createGameRelease({ region: 'other', releasedAt: '2024-01-01T00:00:00Z', title: 'First' }),
      createGameRelease({ region: undefined, releasedAt: '2024-01-01T00:00:00Z', title: 'Second' }),
    ];

    // ACT
    const { result } = renderHook(() => useDeduplicatedReleases(releases));

    // ASSERT
    // ... both are treated as WW with the same date, so only one should remain ...
    expect(result.current).toHaveLength(1);
    expect(result.current[0].title).toEqual('First');
  });

  it('given releases with a null or undefined region, treats them as "WW"', () => {
    // ARRANGE
    const releases = [
      createGameRelease({ region: null, releasedAt: '2024-01-01T00:00:00Z' }),
      createGameRelease({ region: undefined, releasedAt: '2024-01-01T00:00:00Z' }),
      createGameRelease({ region: 'worldwide', releasedAt: '2024-01-01T00:00:00Z' }),
    ];

    // ACT
    const { result } = renderHook(() => useDeduplicatedReleases(releases));

    // ASSERT
    // ... all three are treated as WW with same date, so only one should remain ...
    expect(result.current).toHaveLength(1);
  });

  it('given releases with null dates, groups them together', () => {
    // ARRANGE
    const releases = [
      createGameRelease({ region: 'na', releasedAt: null, title: 'First' }),
      createGameRelease({ region: 'na', releasedAt: undefined, title: 'Second' }),
    ];

    // ACT
    const { result } = renderHook(() => useDeduplicatedReleases(releases));

    // ASSERT
    expect(result.current).toHaveLength(1);
    expect(result.current[0].title).toEqual('First');
  });

  it('given releases with different times on the same date, deduplicates based on date only', () => {
    // ARRANGE
    const releases = [
      createGameRelease({ region: 'jp', releasedAt: '2024-01-01T00:00:00Z' }),
      createGameRelease({ region: 'jp', releasedAt: '2024-01-01T23:59:59Z' }),
    ];

    // ACT
    const { result } = renderHook(() => useDeduplicatedReleases(releases));

    // ASSERT
    // ... same region and date (ignoring time), so only one should remain ..
    expect(result.current).toHaveLength(1);
  });

  it('given a mix of duplicate and unique releases, filters out only the duplicates', () => {
    // ARRANGE
    const releases = [
      createGameRelease({ region: 'na', releasedAt: '2024-01-01T00:00:00Z' }),
      createGameRelease({ region: 'jp', releasedAt: '2024-01-01T00:00:00Z' }),
      createGameRelease({ region: 'na', releasedAt: '2024-01-01T12:00:00Z' }), // duplicate
      createGameRelease({ region: 'eu', releasedAt: '2024-01-02T00:00:00Z' }),
      createGameRelease({ region: 'worldwide', releasedAt: '2024-01-03T00:00:00Z' }),
      createGameRelease({ region: 'other', releasedAt: '2024-01-03T00:00:00Z' }), // duplicate (both WW)
    ];

    // ACT
    const { result } = renderHook(() => useDeduplicatedReleases(releases));

    // ASSERT
    expect(result.current).toHaveLength(4);
  });
});
