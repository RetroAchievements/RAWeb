import { createGameSet } from '@/test/factories';

import { buildMiscRowElements } from './buildMiscRowElements';

describe('Util: buildMiscRowElements', () => {
  it('is defined', () => {
    // ASSERT
    expect(buildMiscRowElements).toBeDefined();
  });

  it('returns empty array when no uncategorized hubs exist', () => {
    // ARRANGE
    const allGameHubs: App.Platform.Data.GameSet[] = [];
    const usedHubIds = new Set<number>();

    // ACT
    const result = buildMiscRowElements(allGameHubs, usedHubIds, { keepPrefixFor: ['Fangames'] });

    // ASSERT
    expect(result).toEqual([]);
  });

  it('collects uncategorized hubs and preserves specified prefixes', () => {
    // ARRANGE
    const fangameHub = createGameSet({
      id: 123,
      title: '[Fangames - Mario]',
      type: 'hub',
    });
    const customHub = createGameSet({
      id: 456,
      title: '[Custom - Category]',
      type: 'hub',
    });
    const allGameHubs = [fangameHub, customHub];
    const usedHubIds = new Set<number>();

    // ACT
    const result = buildMiscRowElements(allGameHubs, usedHubIds, { keepPrefixFor: ['Fangames'] });

    // ASSERT
    expect(result).toEqual([
      { label: 'Category', hubId: 456, href: ['hub.show', 456] },
      { label: 'Fangames - Mario', hubId: 123, href: ['hub.show', 123] },
    ]);
  });

  it('excludes already used hub IDs', () => {
    // ARRANGE
    const hub1 = createGameSet({
      id: 123,
      title: '[Custom - Something]',
      type: 'hub',
    });
    const hub2 = createGameSet({
      id: 456,
      title: '[Custom - Other]',
      type: 'hub',
    });
    const allGameHubs = [hub1, hub2];
    const usedHubIds = new Set([123]); // !! hub 123 is already used

    // ACT
    const result = buildMiscRowElements(allGameHubs, usedHubIds, { keepPrefixFor: ['Fangames'] });

    // ASSERT
    expect(result).toEqual([{ label: 'Other', hubId: 456, href: ['hub.show', 456] }]);
  });

  it('excludes Series hubs', () => {
    // ARRANGE
    const seriesHub = createGameSet({
      id: 123,
      title: '[Series - Zelda]',
      type: 'hub',
    });
    const customHub = createGameSet({
      id: 456,
      title: '[Custom - Something]',
      type: 'hub',
    });
    const allGameHubs = [seriesHub, customHub];
    const usedHubIds = new Set<number>();

    // ACT
    const result = buildMiscRowElements(allGameHubs, usedHubIds, { keepPrefixFor: ['Fangames'] });

    // ASSERT
    expect(result).toEqual([{ label: 'Something', hubId: 456, href: ['hub.show', 456] }]);
  });

  it('excludes Meta team hubs', () => {
    // ARRANGE
    const metaTeamHub = createGameSet({
      id: 123,
      title: '[Meta|QA - Testing]',
      type: 'hub',
    });
    const customHub = createGameSet({
      id: 456,
      title: '[Custom - Something]',
      type: 'hub',
    });
    const allGameHubs = [metaTeamHub, customHub];
    const usedHubIds = new Set<number>();

    // ACT
    const result = buildMiscRowElements(allGameHubs, usedHubIds, { keepPrefixFor: ['Fangames'] });

    // ASSERT
    expect(result).toEqual([{ label: 'Something', hubId: 456, href: ['hub.show', 456] }]);
  });

  it('preserves prefixes case-insensitively', () => {
    // ARRANGE
    const fangameHub = createGameSet({
      id: 123,
      title: '[FANGAMES - Sonic]',
      type: 'hub',
    });
    const allGameHubs = [fangameHub];
    const usedHubIds = new Set<number>();

    // ACT
    const result = buildMiscRowElements(allGameHubs, usedHubIds, { keepPrefixFor: ['Fangames'] });

    // ASSERT
    expect(result).toEqual([{ label: 'FANGAMES - Sonic', hubId: 123, href: ['hub.show', 123] }]);
  });

  it('works with hubs without square brackets', () => {
    // ARRANGE
    const fangameHub = createGameSet({
      id: 123,
      title: 'Fangames - Pokemon', // !! no square brackets
      type: 'hub',
    });
    const customHub = createGameSet({
      id: 456,
      title: 'Random - Something', // !! no square brackets
      type: 'hub',
    });
    const allGameHubs = [fangameHub, customHub];
    const usedHubIds = new Set<number>();

    // ACT
    const result = buildMiscRowElements(allGameHubs, usedHubIds, { keepPrefixFor: ['Fangames'] });

    // ASSERT
    expect(result).toEqual([
      { label: 'Fangames - Pokemon', hubId: 123, href: ['hub.show', 123] },
      { label: 'Something', hubId: 456, href: ['hub.show', 456] },
    ]);
  });

  it('handles hubs without dashes', () => {
    // ARRANGE
    const simpleHub = createGameSet({
      id: 123,
      title: '[SimpleHub]', // !! no dashes
      type: 'hub',
    });
    const allGameHubs = [simpleHub];
    const usedHubIds = new Set<number>();

    // ACT
    const result = buildMiscRowElements(allGameHubs, usedHubIds, { keepPrefixFor: ['Fangames'] });

    // ASSERT
    expect(result).toEqual([{ label: 'SimpleHub', hubId: 123, href: ['hub.show', 123] }]);
  });

  it('sorts results alphabetically', () => {
    // ARRANGE
    const hubZ = createGameSet({
      id: 123,
      title: '[Custom - Zebra]',
      type: 'hub',
    });
    const hubA = createGameSet({
      id: 456,
      title: '[Custom - Apple]',
      type: 'hub',
    });
    const hubM = createGameSet({
      id: 789,
      title: '[Custom - Monkey]',
      type: 'hub',
    });
    const allGameHubs = [hubZ, hubA, hubM];
    const usedHubIds = new Set<number>();

    // ACT
    const result = buildMiscRowElements(allGameHubs, usedHubIds, { keepPrefixFor: ['Fangames'] });

    // ASSERT
    expect(result).toEqual([
      { label: 'Apple', hubId: 456, href: ['hub.show', 456] },
      { label: 'Monkey', hubId: 789, href: ['hub.show', 789] },
      { label: 'Zebra', hubId: 123, href: ['hub.show', 123] },
    ]);
  });
});
