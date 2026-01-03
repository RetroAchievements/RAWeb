import {
  createAchievement,
  createAchievementSet,
  createGame,
  createGameAchievementSet,
  createSystem,
} from '@/test/factories';
import { renderHook } from '@/test/setup';

import { useGameMetaDescription } from './useGameMetaDescription';

describe('Hook: useGameMetaDescription', () => {
  it('is defined', () => {
    // ASSERT
    expect(useGameMetaDescription).toBeDefined();
  });

  it('given the user is viewing published achievements and none are published, returns the correct message', () => {
    // ARRANGE
    const system = createSystem({ name: 'NES' });
    const game = createGame({ system, achievementsPublished: 0 });

    const { result } = renderHook(() => useGameMetaDescription(), {
      pageProps: {
        backingGame: game,
        game,
        isViewingPublishedAchievements: true,
        selectableGameAchievementSets: [],
      },
    });

    // ASSERT
    expect(result.current.description).toContain('No achievements have been published yet');
    expect(result.current.noindex).toEqual(false);
  });

  it('given the user is viewing published achievements and achievements are published, returns the correct message', () => {
    // ARRANGE
    const system = createSystem({ name: 'NES' });
    const game = createGame({
      title: 'Super Mario Bros.',
      system,
      achievementsPublished: 100,
      pointsTotal: 800,
    });

    const { result } = renderHook(() => useGameMetaDescription(), {
      pageProps: {
        backingGame: game,
        game,
        isViewingPublishedAchievements: true,
        selectableGameAchievementSets: [],
      },
    });

    // ASSERT
    expect(result.current.description).toEqual(
      'There are 100 achievements worth 800 points. Super Mario Bros. for NES - explore and compete on this classic game at RetroAchievements.',
    );
    expect(result.current.noindex).toEqual(false);
  });

  it('given the user is viewing published achievements with large point values, formats stuff correctly with commas', () => {
    // ARRANGE
    const system = createSystem({ name: 'SNES' });
    const game = createGame({
      title: 'The Legend of Zelda',
      system,
      achievementsPublished: 50,
      pointsTotal: 1200,
    });

    const { result } = renderHook(() => useGameMetaDescription(), {
      pageProps: {
        backingGame: game,
        game,
        isViewingPublishedAchievements: true,
        selectableGameAchievementSets: [],
      },
    });

    // ASSERT
    expect(result.current.description).toEqual(
      'There are 50 achievements worth 1,200 points. The Legend of Zelda for SNES - explore and compete on this classic game at RetroAchievements.',
    );
  });

  it('given the user is viewing unpublished achievements and there are achievements uploaded, returns the unpublished message and noindex', () => {
    // ARRANGE
    const system = createSystem({ name: 'Genesis/Mega Drive' });

    const achievements = [
      createAchievement({ points: 10, pointsWeighted: 50 }),
      createAchievement({ points: 15, pointsWeighted: 75 }),
      createAchievement({ points: 10, pointsWeighted: 77 }),
    ];

    const achievementSet = createAchievementSet({ achievements });
    const gameAchievementSet = createGameAchievementSet({ achievementSet });

    const game = createGame({
      title: 'Sonic the Hedgehog',
      system,
      gameAchievementSets: [gameAchievementSet],
    });

    const { result } = renderHook(() => useGameMetaDescription(), {
      pageProps: {
        backingGame: game,
        game,
        isViewingPublishedAchievements: false,
      },
    });

    // ASSERT
    expect(result.current.description).toEqual(
      'There are 3 unpublished achievements worth 35 points. Sonic the Hedgehog for Genesis/Mega Drive - explore and compete on this classic game at RetroAchievements.',
    );
    expect(result.current.noindex).toEqual(true);
  });

  it('given the user is viewing unpublished achievements but none have been uploaded, returns the empty state message and noindex', () => {
    // ARRANGE
    const system = createSystem({ name: 'NES' });

    const achievementSet = createAchievementSet({ achievements: [] });
    const gameAchievementSet = createGameAchievementSet({ achievementSet });

    const game = createGame({
      title: 'Empty Game',
      system,
      gameAchievementSets: [gameAchievementSet],
    });

    const { result } = renderHook(() => useGameMetaDescription(), {
      pageProps: {
        backingGame: game,
        game,
        isViewingPublishedAchievements: false,
      },
    });

    // ASSERT
    expect(result.current.description).toEqual(
      'There are no unpublished achievements for Empty Game (NES).',
    );
    expect(result.current.noindex).toEqual(true);
  });

  it('given the user is viewing unpublished achievements with large point values, formats stuff correctly with commas', () => {
    // ARRANGE
    const system = createSystem({ name: 'SNES' });

    const achievements = Array.from({ length: 50 }, () =>
      createAchievement({ points: 30, pointsWeighted: 240 }),
    );

    const achievementSet = createAchievementSet({ achievements });
    const gameAchievementSet = createGameAchievementSet({ achievementSet });

    const game = createGame({
      title: 'Big Point Game',
      system,
      gameAchievementSets: [gameAchievementSet],
    });

    // ACT
    const { result } = renderHook(() => useGameMetaDescription(), {
      pageProps: {
        backingGame: game,
        game,
        isViewingPublishedAchievements: false,
      },
    });

    // ASSERT
    expect(result.current.description).toEqual(
      'There are 50 unpublished achievements worth 1,500 points. Big Point Game for SNES - explore and compete on this classic game at RetroAchievements.',
    );
  });

  it('given there is a targetAchievementSetId, filters meta details correctly', () => {
    // ARRANGE
    const system = createSystem({ name: 'NES' });

    const targetAchievements = [
      createAchievement({ points: 5, pointsWeighted: 10 }),
      createAchievement({ points: 10, pointsWeighted: 20 }),
    ];
    const targetAchievementSet = createAchievementSet({
      id: 100,
      achievements: targetAchievements,
    });
    const targetGameAchievementSet = createGameAchievementSet({
      achievementSet: targetAchievementSet,
    });

    const otherAchievements = [
      createAchievement({ points: 50, pointsWeighted: 100 }),
      createAchievement({ points: 50, pointsWeighted: 100 }),
      createAchievement({ points: 50, pointsWeighted: 100 }),
    ];
    const otherAchievementSet = createAchievementSet({ id: 200, achievements: otherAchievements });
    const otherGameAchievementSet = createGameAchievementSet({
      achievementSet: otherAchievementSet,
    });

    const game = createGame({
      title: 'Multiset Game',
      system,
      gameAchievementSets: [targetGameAchievementSet, otherGameAchievementSet],
    });

    // ACT
    const { result } = renderHook(() => useGameMetaDescription(), {
      pageProps: {
        backingGame: game,
        game,
        isViewingPublishedAchievements: false,
        targetAchievementSetId: 100,
      },
    });

    // ASSERT
    expect(result.current.description).toEqual(
      'There are 2 unpublished achievements worth 15 points. Multiset Game for NES - explore and compete on this classic game at RetroAchievements.',
    );
    expect(result.current.noindex).toEqual(true);
  });
});
