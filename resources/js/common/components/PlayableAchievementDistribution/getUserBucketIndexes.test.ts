import { createPlayerGame } from '@/test/factories';

import { getUserBucketIndexes } from './getUserBucketIndexes';

describe('Util: getUserBucketIndexes', () => {
  it('is defined', () => {
    // ASSERT
    expect(getUserBucketIndexes).toBeDefined();
  });

  it('given the player game is null, returns an empty object', () => {
    // ARRANGE
    const buckets: App.Platform.Data.PlayerAchievementChartBucket[] = [
      { start: 0, end: 10, softcore: 5, hardcore: 3 },
      { start: 11, end: 20, softcore: 10, hardcore: 8 },
    ];
    const playerGame = null;

    // ACT
    const result = getUserBucketIndexes(buckets, playerGame);

    // ASSERT
    expect(result).toEqual({});
  });

  it('given the player has hardcore achievements, returns the correct hardcore bucket index', () => {
    // ARRANGE
    const buckets: App.Platform.Data.PlayerAchievementChartBucket[] = [
      { start: 0, end: 10, softcore: 5, hardcore: 3 },
      { start: 11, end: 20, softcore: 10, hardcore: 8 },
      { start: 21, end: 30, softcore: 15, hardcore: 12 },
    ];
    const playerGame = createPlayerGame({
      achievementsUnlockedHardcore: 15,
      achievementsUnlocked: 15,
    });

    // ACT
    const result = getUserBucketIndexes(buckets, playerGame);

    // ASSERT
    expect(result).toEqual({ userHardcoreIndex: 1 });
  });

  it('given the player has softcore achievements different from hardcore, returns both bucket indexes', () => {
    // ARRANGE
    const buckets: App.Platform.Data.PlayerAchievementChartBucket[] = [
      { start: 0, end: 10, softcore: 5, hardcore: 3 },
      { start: 11, end: 20, softcore: 10, hardcore: 8 },
      { start: 21, end: 30, softcore: 15, hardcore: 12 },
    ];
    const playerGame = createPlayerGame({
      achievementsUnlockedHardcore: 5,
      achievementsUnlocked: 25,
    });

    // ACT
    const result = getUserBucketIndexes(buckets, playerGame);

    // ASSERT
    expect(result).toEqual({ userHardcoreIndex: 0, userSoftcoreIndex: 2 });
  });

  it('given the player has softcore achievements equal to hardcore, only returns hardcore index', () => {
    // ARRANGE
    const buckets: App.Platform.Data.PlayerAchievementChartBucket[] = [
      { start: 0, end: 10, softcore: 5, hardcore: 3 },
      { start: 11, end: 20, softcore: 10, hardcore: 8 },
    ];
    const playerGame = createPlayerGame({
      achievementsUnlockedHardcore: 15,
      achievementsUnlocked: 15,
    });

    // ACT
    const result = getUserBucketIndexes(buckets, playerGame);

    // ASSERT
    expect(result).toEqual({ userHardcoreIndex: 1 });
  });

  it('given the player has no hardcore achievements but has softcore, only returns softcore index', () => {
    // ARRANGE
    const buckets: App.Platform.Data.PlayerAchievementChartBucket[] = [
      { start: 0, end: 10, softcore: 5, hardcore: 3 },
      { start: 11, end: 20, softcore: 10, hardcore: 8 },
    ];
    const playerGame = createPlayerGame({
      achievementsUnlockedHardcore: 0,
      achievementsUnlocked: 15,
    });

    // ACT
    const result = getUserBucketIndexes(buckets, playerGame);

    // ASSERT
    expect(result).toEqual({ userSoftcoreIndex: 1 });
  });

  it('given the achievement counts do not match any bucket range, returns an empty object', () => {
    // ARRANGE
    const buckets: App.Platform.Data.PlayerAchievementChartBucket[] = [
      { start: 10, end: 20, softcore: 5, hardcore: 3 },
      { start: 30, end: 40, softcore: 10, hardcore: 8 },
    ];
    const playerGame = createPlayerGame({
      achievementsUnlockedHardcore: 5,
      achievementsUnlocked: 25,
    });

    // ACT
    const result = getUserBucketIndexes(buckets, playerGame);

    // ASSERT
    expect(result).toEqual({});
  });

  it('given the achievement counts are at the exact boundaries of buckets, returns the correct indexes', () => {
    // ARRANGE
    const buckets: App.Platform.Data.PlayerAchievementChartBucket[] = [
      { start: 0, end: 10, softcore: 5, hardcore: 3 },
      { start: 11, end: 20, softcore: 10, hardcore: 8 },
    ];
    const playerGame = createPlayerGame({
      achievementsUnlockedHardcore: 10,
      achievementsUnlocked: 20,
    });

    // ACT
    const result = getUserBucketIndexes(buckets, playerGame);

    // ASSERT
    expect(result).toEqual({ userHardcoreIndex: 0, userSoftcoreIndex: 1 });
  });
});
