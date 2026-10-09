import {
  createAchievement,
  createPlayerAchievementSet,
} from '@/test/factories';

import { getCanManageProgress } from './getCanManageProgress';

describe('Hook: useCanManageProgress', () => {
  it('given no achievements exist, returns false', () => {
    // ARRANGE
    const result = getCanManageProgress(123, [], {});

    // ASSERT
    expect(result).toEqual(false);
  });

  it('given the user has no progress, returns false', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedAt: undefined, unlockedHardcoreAt: undefined }),
      createAchievement({ unlockedAt: undefined, unlockedHardcoreAt: undefined }),
      createAchievement({ unlockedAt: undefined, unlockedHardcoreAt: undefined }),
    ];

    const result = getCanManageProgress(123, achievements, {});

    // ASSERT
    expect(result).toEqual(false);
  });

  it('given the user has progress, returns true', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedAt: '2024-01-01T00:00:00Z', unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({ unlockedAt: '2024-01-01T00:00:00Z', unlockedHardcoreAt: undefined }),
      createAchievement({ unlockedAt: undefined, unlockedHardcoreAt: undefined }),
    ];

    const result = getCanManageProgress(123, achievements, {});

    // ASSERT
    expect(result).toEqual(true);
  });

  // simulates a case where a set was demoted.
  // a player should still be able to fully reset the game if they want.
  it('given no achievements exist, but the user has a completion timestamp, returns true', () => {
    // ARRANGE
    const result = getCanManageProgress(123, [], {
      123: createPlayerAchievementSet({
        completedAt: '2024-05-15T14:30:00.000000Z', // !!
        completedHardcoreAt: null,
        timeTaken: 7200,
        timeTakenHardcore: null,
      }),
    });

    // ASSERT
    expect(result).toEqual(true);
  });

  // If a game doesn't have subsets it may not pass an achievement set id through the PageProps
  it('given no achievements exist and no achievement set id is provided, but the user has a completion timestamp, returns true', () => {
    // ARRANGE
    const result = getCanManageProgress(null, [], {
      123: createPlayerAchievementSet({
        completedAt: '2024-05-15T14:30:00.000000Z', // !!
        completedHardcoreAt: null,
        timeTaken: 7200,
        timeTakenHardcore: null,
      }),
    });

    // ASSERT
    expect(result).toEqual(true);
  });
});
