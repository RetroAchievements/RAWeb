import { renderHook } from '@/test';
import { createGame, createGameSet } from '@/test/factories';

import { useAllMetaRowElements } from './useAllMetaRowElements';

describe('Hook: useAllMetaRowElements', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const game = createGame();
    const allGameHubs: App.Platform.Data.GameSet[] = [];

    // ACT
    const { result } = renderHook(() => useAllMetaRowElements(game, allGameHubs));

    // ASSERT
    expect(result.current).toBeTruthy();
  });

  it('given a game with developer field, uses it as fallback when no matching hubs exist', () => {
    // ARRANGE
    const game = createGame({ developer: 'Test Developer' });
    const allGameHubs: App.Platform.Data.GameSet[] = [];

    // ACT
    const { result } = renderHook(() => useAllMetaRowElements(game, allGameHubs));

    // ASSERT
    expect(result.current.developerRowElements).toEqual([
      { label: 'Test Developer', href: undefined },
    ]);
  });

  it('given a game with publisher field, uses it as fallback when no matching hubs exist', () => {
    // ARRANGE
    const game = createGame({ publisher: 'Test Publisher' });
    const allGameHubs: App.Platform.Data.GameSet[] = [];

    // ACT
    const { result } = renderHook(() => useAllMetaRowElements(game, allGameHubs));

    // ASSERT
    expect(result.current.publisherRowElements).toEqual([
      { label: 'Test Publisher', href: undefined },
    ]);
  });

  it('given a game with genre field, uses it as fallback when no matching hubs exist', () => {
    // ARRANGE
    const game = createGame({ genre: 'Action, Adventure' });
    const allGameHubs: App.Platform.Data.GameSet[] = [];

    // ACT
    const { result } = renderHook(() => useAllMetaRowElements(game, allGameHubs));

    // ASSERT
    expect(result.current.genreRowElements).toEqual([
      { label: 'Action', href: undefined },
      { label: 'Adventure', href: undefined },
    ]);
  });

  it('given a developer hub, properly formats the developer row elements', () => {
    // ARRANGE
    const game = createGame();
    const developerHub = createGameSet({
      id: 123,
      title: '[Developer - Capcom]',
      type: 'hub',
    });
    const allGameHubs = [developerHub];

    // ACT
    const { result } = renderHook(() => useAllMetaRowElements(game, allGameHubs));

    // ASSERT
    expect(result.current.developerRowElements).toEqual([
      { label: 'Capcom', hubId: 123, href: ['hub.show', 123] },
    ]);
  });

  it('given a publisher hub, properly formats the publisher row elements', () => {
    // ARRANGE
    const game = createGame();
    const publisherHub = createGameSet({
      id: 456,
      title: '[Publisher - Nintendo]',
      type: 'hub',
    });
    const allGameHubs = [publisherHub];

    // ACT
    const { result } = renderHook(() => useAllMetaRowElements(game, allGameHubs));

    // ASSERT
    expect(result.current.publisherRowElements).toEqual([
      { label: 'Nintendo', hubId: 456, href: ['hub.show', 456] },
    ]);
  });

  it('given a genre hub, properly formats the genre row elements', () => {
    // ARRANGE
    const game = createGame();
    const genreHub = createGameSet({
      id: 789,
      title: '[Genre - RPG]',
      type: 'hub',
    });
    const allGameHubs = [genreHub];

    // ACT
    const { result } = renderHook(() => useAllMetaRowElements(game, allGameHubs));

    // ASSERT
    expect(result.current.genreRowElements).toEqual([
      { label: 'RPG', hubId: 789, href: ['hub.show', 789] },
    ]);
  });

  it('given subgenre hubs, properly formats and includes them in genre row elements', () => {
    // ARRANGE
    const game = createGame();
    const genreHub = createGameSet({
      id: 123,
      title: '[Genre - RPG]',
      type: 'hub',
    });
    const subgenreHub = createGameSet({
      id: 456,
      title: '[Subgenre - JRPG]',
      type: 'hub',
    });
    const allGameHubs = [genreHub, subgenreHub];

    // ACT
    const { result } = renderHook(() => useAllMetaRowElements(game, allGameHubs));

    // ASSERT
    expect(result.current.genreRowElements).toEqual([
      { label: 'JRPG', hubId: 456, href: ['hub.show', 456] },
      { label: 'RPG', hubId: 123, href: ['hub.show', 123] },
    ]);
  });

  it('given language hubs, properly formats them and marks alternate languages with asterisks', () => {
    // ARRANGE
    const game = createGame();
    const languageHub = createGameSet({
      id: 123,
      title: '[Meta - Language - English]',
      type: 'hub',
    });
    const languagePatchHub = createGameSet({
      id: 456,
      title: '[Meta - Language Patch - Spanish]',
      type: 'hub',
    });
    const allGameHubs = [languageHub, languagePatchHub];

    // ACT
    const { result } = renderHook(() => useAllMetaRowElements(game, allGameHubs));

    // ASSERT
    expect(result.current.languageRowElements).toEqual([
      { label: 'English', hubId: 123, href: ['hub.show', 123] },
      { label: 'Spanish*', hubId: 456, href: ['hub.show', 456] },
    ]);
  });

  it('properly collects all used hub IDs', () => {
    // ARRANGE
    const game = createGame();
    const developerHub = createGameSet({
      id: 123,
      title: '[Developer - Capcom]',
      type: 'hub',
    });
    const genreHub = createGameSet({
      id: 456,
      title: '[Genre - RPG]',
      type: 'hub',
    });
    const languageHub = createGameSet({
      id: 789,
      title: '[Meta - Language - English]',
      type: 'hub',
    });
    const allGameHubs = [developerHub, genreHub, languageHub];

    // ACT
    const { result } = renderHook(() => useAllMetaRowElements(game, allGameHubs));

    // ASSERT
    expect(result.current.allUsedHubIds).toEqual([123, 456, 789]);
  });

  it('given multiple hubs of the same category, sorts them alphabetically', () => {
    // ARRANGE
    const game = createGame();
    const genreHub1 = createGameSet({
      id: 123,
      title: '[Genre - RPG]',
      type: 'hub',
    });
    const genreHub2 = createGameSet({
      id: 456,
      title: '[Genre - Action]',
      type: 'hub',
    });
    const genreHub3 = createGameSet({
      id: 789,
      title: '[Genre - Platformer]',
      type: 'hub',
    });
    const allGameHubs = [genreHub1, genreHub2, genreHub3];

    // ACT
    const { result } = renderHook(() => useAllMetaRowElements(game, allGameHubs));

    // ASSERT
    expect(result.current.genreRowElements).toEqual([
      { label: 'Action', hubId: 456, href: ['hub.show', 456] },
      { label: 'Platformer', hubId: 789, href: ['hub.show', 789] },
      { label: 'RPG', hubId: 123, href: ['hub.show', 123] },
    ]);
  });

  it('excludes meta language hubs from RA feature row elements', () => {
    // ARRANGE
    const game = createGame();
    const metaLanguageHub = createGameSet({
      id: 123,
      title: '[Meta - Language - English]',
      type: 'hub',
    });
    const metaFeatureHub = createGameSet({
      id: 456,
      title: '[Meta - Feature]',
      type: 'hub',
    });
    const allGameHubs = [metaLanguageHub, metaFeatureHub];

    // ACT
    const { result } = renderHook(() => useAllMetaRowElements(game, allGameHubs));

    // ASSERT
    expect(result.current.raFeatureRowElements).toEqual([
      { label: 'Feature', hubId: 456, href: ['hub.show', 456] },
    ]);
    expect(result.current.languageRowElements).toEqual([
      { label: 'English', hubId: 123, href: ['hub.show', 123] },
    ]);
  });

  it('uses keepPrefixFor parameter to maintain prefixes for specified categories', () => {
    // ARRANGE
    const game = createGame();
    const metaHub1 = createGameSet({
      id: 123,
      title: '[Meta - Special Feature]',
      type: 'hub',
    });
    const metaHub2 = createGameSet({
      id: 456,
      title: '[Meta - Regular Feature]',
      type: 'hub',
    });
    const allGameHubs = [metaHub1, metaHub2];

    // ACT
    const { result } = renderHook(() => useAllMetaRowElements(game, allGameHubs));

    // ASSERT
    expect(result.current.raFeatureRowElements).toEqual([
      { label: 'Regular Feature', hubId: 456, href: ['hub.show', 456] },
      { label: 'Special Feature', hubId: 123, href: ['hub.show', 123] },
    ]);
  });

  it('collects uncategorized hubs in miscRowElements as a catch-all and preserves specified prefixes', () => {
    // ARRANGE
    const game = createGame();
    const centralHub = createGameSet({
      id: 123,
      title: '[Central - Homebrew]',
      type: 'hub',
    });
    const fangameHub = createGameSet({
      id: 456,
      title: '[Fangames - Mario]',
      type: 'hub',
    });
    const customHub = createGameSet({
      id: 789,
      title: '[Some Custom - Category]',
      type: 'hub',
    });
    const allGameHubs = [centralHub, fangameHub, customHub];

    // ACT
    const { result } = renderHook(() => useAllMetaRowElements(game, allGameHubs));

    // ASSERT
    expect(result.current.miscRowElements).toEqual([
      { label: 'Category', hubId: 789, href: ['hub.show', 789] },
      { label: 'Fangames - Mario', hubId: 456, href: ['hub.show', 456] },
      { label: 'Homebrew', hubId: 123, href: ['hub.show', 123] },
    ]);
  });

  it('excludes Series hubs from miscRowElements', () => {
    // ARRANGE
    const game = createGame();
    const seriesHub = createGameSet({
      id: 123,
      title: '[Series - Zelda]',
      type: 'hub',
    });
    const otherHub = createGameSet({
      id: 456,
      title: '[Custom - Something]',
      type: 'hub',
    });
    const allGameHubs = [seriesHub, otherHub];

    // ACT
    const { result } = renderHook(() => useAllMetaRowElements(game, allGameHubs));

    // ASSERT
    expect(result.current.miscRowElements).toEqual([
      { label: 'Something', hubId: 456, href: ['hub.show', 456] },
    ]);
  });

  it('excludes Meta team hubs from miscRowElements', () => {
    // ARRANGE
    const game = createGame();
    const metaTeamHub1 = createGameSet({
      id: 123,
      title: '[Meta|QA - Testing Hub]',
      type: 'hub',
    });
    const metaTeamHub2 = createGameSet({
      id: 456,
      title: '[Meta|DevComp - Competition Hub]',
      type: 'hub',
    });
    const regularHub = createGameSet({
      id: 789,
      title: '[Custom - Something]',
      type: 'hub',
    });
    const allGameHubs = [metaTeamHub1, metaTeamHub2, regularHub];

    // ACT
    const { result } = renderHook(() => useAllMetaRowElements(game, allGameHubs));

    // ASSERT
    expect(result.current.miscRowElements).toEqual([
      { label: 'Something', hubId: 789, href: ['hub.show', 789] },
    ]);
  });

  it('does not include already categorized hubs in miscRowElements', () => {
    // ARRANGE
    const game = createGame();
    const genreHub = createGameSet({
      id: 123,
      title: '[Genre - RPG]',
      type: 'hub',
    });
    const developerHub = createGameSet({
      id: 456,
      title: '[Developer - Nintendo]',
      type: 'hub',
    });
    const uncategorizedHub = createGameSet({
      id: 789,
      title: '[Custom - Something]',
      type: 'hub',
    });
    const allGameHubs = [genreHub, developerHub, uncategorizedHub];

    // ACT
    const { result } = renderHook(() => useAllMetaRowElements(game, allGameHubs));

    // ASSERT
    expect(result.current.genreRowElements).toEqual([
      { label: 'RPG', hubId: 123, href: ['hub.show', 123] },
    ]);
    expect(result.current.developerRowElements).toEqual([
      { label: 'Nintendo', hubId: 456, href: ['hub.show', 456] },
    ]);
    expect(result.current.miscRowElements).toEqual([
      { label: 'Something', hubId: 789, href: ['hub.show', 789] },
    ]);
  });

  it('correctly handles hubs without dashes in their title for miscRowElements', () => {
    // ARRANGE
    const game = createGame();
    const simpleHub = createGameSet({
      id: 123,
      title: '[SimpleHub]',
      type: 'hub',
    });
    const allGameHubs = [simpleHub];

    // ACT
    const { result } = renderHook(() => useAllMetaRowElements(game, allGameHubs));

    // ASSERT
    expect(result.current.miscRowElements).toEqual([
      { label: 'SimpleHub', hubId: 123, href: ['hub.show', 123] },
    ]);
  });

  it('preserves prefixes case-insensitively', () => {
    // ARRANGE
    const game = createGame();
    const fangameHub = createGameSet({
      id: 123,
      title: '[FANGAMES - Sonic]',
      type: 'hub',
    });
    const centralHub = createGameSet({
      id: 456,
      title: '[central - Emulation]',
      type: 'hub',
    });
    const allGameHubs = [fangameHub, centralHub];

    // ACT
    const { result } = renderHook(() => useAllMetaRowElements(game, allGameHubs));

    // ASSERT
    expect(result.current.miscRowElements).toEqual([
      { label: 'Emulation', hubId: 456, href: ['hub.show', 456] },
      { label: 'FANGAMES - Sonic', hubId: 123, href: ['hub.show', 123] },
    ]);
  });

  it('can process misc hubs that lack square brackets', () => {
    // ARRANGE
    const game = createGame();
    const fangameHub = createGameSet({
      id: 123,
      title: 'Fangames - Pokemon',
      type: 'hub',
    });
    const centralHub = createGameSet({
      id: 456,
      title: 'Central - Arcade',
      type: 'hub',
    });
    const customHub = createGameSet({
      id: 789,
      title: 'Random - Something',
      type: 'hub',
    });
    const allGameHubs = [fangameHub, centralHub, customHub];

    // ACT
    const { result } = renderHook(() => useAllMetaRowElements(game, allGameHubs));

    // ASSERT
    expect(result.current.miscRowElements).toEqual([
      { label: 'Arcade', hubId: 456, href: ['hub.show', 456] },
      { label: 'Fangames - Pokemon', hubId: 123, href: ['hub.show', 123] },
      { label: 'Something', hubId: 789, href: ['hub.show', 789] },
    ]);
  });
});
