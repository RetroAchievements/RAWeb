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
});
