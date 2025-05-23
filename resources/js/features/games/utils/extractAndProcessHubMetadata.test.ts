import { createGameSet } from '@/test/factories';

import { extractAndProcessHubMetadata } from './extractAndProcessHubMetadata';

describe('Util: extractAndProcessHubMetadata', () => {
  it('is defined', () => {
    // ASSERT
    expect(extractAndProcessHubMetadata).toBeDefined();
  });

  it('given a fallback value is provided, includes it in the results', () => {
    // ARRANGE
    const hubs: App.Platform.Data.GameSet[] = [];
    const fallbackValue = 'Default, Another Default';

    // ACT
    const result = extractAndProcessHubMetadata(hubs, 'Primary', [], [], [], fallbackValue);

    // ASSERT
    expect(result).toEqual([{ label: 'Another Default' }, { label: 'Default' }]);
  });

  it('given a hub matches the primary label pattern, extracts its metadata', () => {
    // ARRANGE
    const hubs = [
      createGameSet({
        id: 123,
        title: '[Primary - Test Hub]',
      }),
    ];

    // ACT
    const result = extractAndProcessHubMetadata(hubs, 'Primary', [], ['test'], []);

    // ASSERT
    expect(result).toEqual([{ label: 'Test Hub', hubId: 123 }]);
  });

  it('given a hub has no title, handles it gracefully', () => {
    // ARRANGE
    const hubs = [
      createGameSet({
        id: 123,
        title: undefined,
      }),
    ];

    // ACT
    const result = extractAndProcessHubMetadata(hubs, 'Primary', [], ['hub'], []);

    // ASSERT
    expect(result).toEqual([]);
  });

  it('given a hub with id overrides an existing label without id', () => {
    // ARRANGE
    const hubs = [
      createGameSet({
        id: undefined,
        title: '[Primary - Test Hub]',
      }),
      createGameSet({
        id: 123,
        title: '[Primary - Test Hub]',
      }),
    ];

    // ACT
    const result = extractAndProcessHubMetadata(hubs, 'Primary', [], ['hub'], []);

    // ASSERT
    expect(result).toEqual([{ label: 'Test Hub', hubId: 123 }]);
  });

  it('given a hub matches an alt label pattern and markAltLabels is true, adds an asterisk to the label', () => {
    // ARRANGE
    const hubs = [
      createGameSet({
        id: 123,
        title: '[Alt - Test Hub]',
      }),
    ];

    // ACT
    const result = extractAndProcessHubMetadata(
      hubs,
      'Primary',
      ['Alt'],
      ['test'],
      [],
      undefined,
      false,
      true,
    );

    // ASSERT
    expect(result).toEqual([{ label: 'Test Hub*', hubId: 123 }]);
  });

  it('given altLabelsLast is true, sorts alt labels after primary labels alphabetically', () => {
    // ARRANGE
    const hubs = [
      createGameSet({
        id: 123,
        title: '[Alt - B Hub]',
      }),
      createGameSet({
        id: 456,
        title: '[Alt - A Hub]',
      }),
      createGameSet({
        id: 789,
        title: '[Primary - D Hub]',
      }),
      createGameSet({
        id: 101,
        title: '[Primary - C Hub]',
      }),
    ];

    // ACT
    const result = extractAndProcessHubMetadata(
      hubs,
      'Primary',
      ['Alt'],
      ['hub'],
      [],
      undefined,
      true,
      false,
    );

    // ASSERT
    expect(result).toEqual([
      { label: 'C Hub', hubId: 101 },
      { label: 'D Hub', hubId: 789 },
      { label: 'A Hub', hubId: 456 },
      { label: 'B Hub', hubId: 123 },
    ]);
  });

  it('given altLabelsLast is false, sorts all labels alphabetically', () => {
    // ARRANGE
    const hubs = [
      createGameSet({
        id: 123,
        title: '[Alt - B Hub]',
      }),
      createGameSet({
        id: 456,
        title: '[Primary - A Hub]',
      }),
      createGameSet({
        id: 789,
        title: '[Alt - C Hub]',
      }),
    ];

    // ACT
    const result = extractAndProcessHubMetadata(
      hubs,
      'Primary',
      ['Alt'],
      ['hub'],
      [],
      undefined,
      false,
    );

    // ASSERT
    expect(result).toEqual([
      { label: 'A Hub', hubId: 456 },
      { label: 'B Hub', hubId: 123 },
      { label: 'C Hub', hubId: 789 },
    ]);
  });

  it('given a hub matches an exclude pattern, filters it out', () => {
    // ARRANGE
    const hubs = [
      createGameSet({
        id: 123,
        title: '[Primary - Test Hub]',
      }),
      createGameSet({
        id: 456,
        title: '[Primary - Excluded Hub]',
      }),
    ];

    // ACT
    const result = extractAndProcessHubMetadata(hubs, 'Primary', [], ['hub'], ['excluded']);

    // ASSERT
    expect(result).toEqual([{ label: 'Test Hub', hubId: 123 }]);
  });

  it('given a prefix should be kept, retains it in the label', () => {
    // ARRANGE
    const hubs = [
      createGameSet({
        id: 123,
        title: '[Hack - Test Hub]',
      }),
    ];

    // ACT
    const result = extractAndProcessHubMetadata(
      hubs,
      'Hack',
      [],
      ['hub'],
      [],
      undefined,
      false,
      false,
      ['Hack'],
    );

    // ASSERT
    expect(result).toEqual([{ label: 'Hack - Test Hub', hubId: 123 }]);
  });

  it('given a Hack prefix, replaces "Hacks -" with "Hack -" in the value', () => {
    // ARRANGE
    const hubs = [
      createGameSet({
        id: 123,
        title: '[Hack - Testing Hacks - Hub]',
      }),
    ];

    // ACT
    const result = extractAndProcessHubMetadata(hubs, 'Hack', [], ['hub'], []);

    // ASSERT
    expect(result).toEqual([{ label: 'Testing Hack - Hub', hubId: 123 }]);
  });

  it('given altLabelsLast is true, correctly sorts when comparing alt and non-alt labels in either order', () => {
    // ARRANGE
    const hubs = [
      // !! Alt label first, Primary label second (tests a.isAlt = true).
      createGameSet({
        id: 123,
        title: '[Alt - Z Hub]',
      }),
      createGameSet({
        id: 456,
        title: '[Primary - A Hub]',
      }),

      // !! Primary label first, Alt label second (tests a.isAlt = false).
      createGameSet({
        id: 789,
        title: '[Primary - B Hub]',
      }),
      createGameSet({
        id: 101,
        title: '[Alt - Y Hub]',
      }),
    ];

    // ACT
    const result = extractAndProcessHubMetadata(
      hubs,
      'Primary',
      ['Alt'],
      ['hub'],
      [],
      undefined,
      true,
      false,
    );

    // ASSERT
    expect(result).toEqual([
      { label: 'A Hub', hubId: 456 },
      { label: 'B Hub', hubId: 789 },
      { label: 'Y Hub', hubId: 101 },
      { label: 'Z Hub', hubId: 123 },
    ]);
  });

  it('given a fallback value matches a hub pattern, it gets excluded', () => {
    // ARRANGE
    const hubs: App.Platform.Data.GameSet[] = [];
    const fallbackValue = 'hub Test Value';

    // ACT
    const result = extractAndProcessHubMetadata(hubs, 'Primary', [], ['hub'], [], fallbackValue);

    // ASSERT
    expect(result).toEqual([]);
  });

  it('given a fallback value with multiple values where one matches a hub pattern, only includes non-matching values', () => {
    // ARRANGE
    const hubs: App.Platform.Data.GameSet[] = [];
    const fallbackValue = 'Normal Value, hub Special Value';

    // ACT
    const result = extractAndProcessHubMetadata(hubs, 'Primary', [], ['hub'], [], fallbackValue);

    // ASSERT
    expect(result).toEqual([{ label: 'Normal Value' }]);
  });

  it('given a fallback value that contains a hub pattern in uppercase, still correctly excludes it (case insensitive check)', () => {
    // ARRANGE
    const hubs: App.Platform.Data.GameSet[] = [];
    const fallbackValue = 'HUB Test Value, Normal Value';

    // ACT
    const result = extractAndProcessHubMetadata(hubs, 'Primary', [], ['hub'], [], fallbackValue);

    // ASSERT
    expect(result).toEqual([{ label: 'Normal Value' }]);
  });
});
