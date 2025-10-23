import { createGameSet, createSeriesHub } from '@/test/factories';

import { getSidebarExcludedHubIds } from './getSidebarExcludedHubIds';
import { hubIds } from './hubIds';

describe('Util: getSidebarExcludedHubIds', () => {
  it('excludes the hub matching the series hub', () => {
    // ARRANGE
    const seriesHubId = 123;
    const seriesHub = createSeriesHub({
      hub: createGameSet({ id: seriesHubId }),
    });
    const matchingHub = createGameSet({ id: seriesHubId });
    const regularHub = createGameSet({ id: 456 });
    const hubs = [matchingHub, regularHub];

    // ACT
    const result = getSidebarExcludedHubIds(hubs, seriesHub, []); // !! pass seriesHub, not null

    // ASSERT
    expect(result).toEqual([123, ...Object.values(hubIds)]);
  });

  it('does not exclude event hubs', () => {
    // ARRANGE
    const eventHub = createGameSet({ id: 1, isEventHub: true });
    const regularHub = createGameSet({ id: 2, isEventHub: false });
    const hubs = [eventHub, regularHub];

    // ACT
    const result = getSidebarExcludedHubIds(hubs, null, []);

    // ASSERT
    expect(result).toEqual([...Object.values(hubIds)]);
  });

  it('excludes series hubs but not event hubs', () => {
    // ARRANGE
    const seriesHubId = 123;
    const seriesHub = createSeriesHub({
      hub: createGameSet({ id: seriesHubId }),
    });
    const eventHub = createGameSet({ id: 1, isEventHub: true });
    const matchingHub = createGameSet({ id: seriesHubId });
    const regularHub = createGameSet({ id: 456 });
    const hubs = [eventHub, matchingHub, regularHub];

    // ACT
    const result = getSidebarExcludedHubIds(hubs, seriesHub, []);

    // ASSERT
    expect(result).toEqual([123, ...Object.values(hubIds)]);
  });

  it('includes meta used hub IDs', () => {
    // ARRANGE
    const metaUsedHubIds = [100, 200];
    const eventHub = createGameSet({ id: 1, isEventHub: true });
    const regularHub = createGameSet({ id: 2 });
    const hubs = [eventHub, regularHub];

    // ACT
    const result = getSidebarExcludedHubIds(hubs, null, metaUsedHubIds);

    // ASSERT
    expect(result).toEqual([100, 200, ...Object.values(hubIds)]);
  });

  it('handles empty hubs array', () => {
    // ARRANGE
    const metaUsedHubIds = [100];

    // ACT
    const result = getSidebarExcludedHubIds([], null, metaUsedHubIds);

    // ASSERT
    expect(result).toEqual([100, ...Object.values(hubIds)]);
  });

  it('handles null series hub', () => {
    // ARRANGE
    const regularHub = createGameSet({ id: 1 });
    const hubs = [regularHub];

    // ACT
    const result = getSidebarExcludedHubIds(hubs, null, []);

    // ASSERT
    expect(result).toEqual([...Object.values(hubIds)]);
  });

  it('combines all exclusions correctly', () => {
    // ARRANGE
    const seriesHubId = 300;
    const seriesHub = createSeriesHub({
      hub: createGameSet({ id: seriesHubId }),
    });
    const metaUsedHubIds = [100, 200];
    const eventHub1 = createGameSet({ id: 1, isEventHub: true });
    const eventHub2 = createGameSet({ id: 2, isEventHub: true });
    const matchingHub = createGameSet({ id: seriesHubId });
    const regularHub = createGameSet({ id: 456 });
    const hubs = [eventHub1, eventHub2, matchingHub, regularHub];

    // ACT
    const result = getSidebarExcludedHubIds(hubs, seriesHub, metaUsedHubIds);

    // ASSERT
    expect(result).toEqual([100, 200, 300, ...Object.values(hubIds)]);
  });
});
