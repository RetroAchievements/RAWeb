import { renderHook } from '@/test';
import {
  createAchievement,
  createPlayerAchievementSet,
} from '@/test/factories';

import { useCanManageProgress } from './useCanManageProgress';

describe('Hook: useCanManageProgress', () => {
  it('given no achievements exist, returns false', () => {
    // ARRANGE
    const { result } = renderHook(() => useCanManageProgress(123, []), {
      pageProps: {
        playerAchievementSets: {},
      },
    });

    // ASSERT
    expect(result.current.canManageProgress).toEqual(false);
  });

  it('given the user has no progress, returns false', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedAt: undefined, unlockedHardcoreAt: undefined }),
      createAchievement({ unlockedAt: undefined, unlockedHardcoreAt: undefined }),
      createAchievement({ unlockedAt: undefined, unlockedHardcoreAt: undefined }),
    ];

    const { result } = renderHook(() => useCanManageProgress(123, achievements), {
      pageProps: {
        playerAchievementSets: {},
      },
    });

    // ASSERT
    expect(result.current.canManageProgress).toEqual(false);
  });

  it('given the user has progress, returns true', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedAt: '2024-01-01T00:00:00Z', unlockedHardcoreAt: '2024-01-01T00:00:00Z' }),
      createAchievement({ unlockedAt: '2024-01-01T00:00:00Z', unlockedHardcoreAt: undefined }),
      createAchievement({ unlockedAt: undefined, unlockedHardcoreAt: undefined }),
    ];

    const { result } = renderHook(() => useCanManageProgress(123, achievements), {
      pageProps: {
        playerAchievementSets: {},
      },
    });

    // ASSERT
    expect(result.current.canManageProgress).toEqual(true);
  });

  // simulates a case where a set was demoted.
  // a player should still be able to fully reset the game if they want.
  it('given no achievements exist, but the user has a completion timestamp, returns true', () => {
    // ARRANGE
    const { result } = renderHook(() => useCanManageProgress(123, []), {
      pageProps: {
        playerAchievementSets: {
          123: createPlayerAchievementSet({
            completedAt: '2024-05-15T14:30:00.000000Z', // !!
            completedHardcoreAt: null,
            timeTaken: 7200,
            timeTakenHardcore: null,
          }),
        },
      },
    });

    // ASSERT
    expect(result.current.canManageProgress).toEqual(true);
  });
});
