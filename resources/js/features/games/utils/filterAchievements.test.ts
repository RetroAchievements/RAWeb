import { createAchievement } from '@/test/factories';

import { filterAchievements } from './filterAchievements';

describe('Util: filterAchievements', () => {
  it('is defined', () => {
    // ASSERT
    expect(filterAchievements).toBeDefined();
  });

  it('given an empty array, returns an empty array', () => {
    // ARRANGE
    const achievements: App.Platform.Data.Achievement[] = [];
    const filters = { showLockedOnly: false, showMissableOnly: false };

    // ACT
    const result = filterAchievements(achievements, filters);

    // ASSERT
    expect(result).toEqual([]);
  });

  it('given no filters are active, returns all achievements', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ unlockedAt: '2024-01-01' }),
      createAchievement({ unlockedAt: undefined, unlockedHardcoreAt: undefined }),
      createAchievement({ type: 'missable' }),
      createAchievement({ type: 'progression' }),
    ];
    const filters = { showLockedOnly: false, showMissableOnly: false };

    // ACT
    const result = filterAchievements(achievements, filters);

    // ASSERT
    expect(result).toEqual(achievements);
  });

  it('given showLockedOnly is true, only returns achievements that are not unlocked', () => {
    // ARRANGE
    const lockedAchievement1 = createAchievement({
      unlockedAt: undefined,
      unlockedHardcoreAt: undefined,
    });
    const lockedAchievement2 = createAchievement({
      unlockedAt: undefined,
      unlockedHardcoreAt: undefined,
    });
    const unlockedSoftcore = createAchievement({
      unlockedAt: '2024-01-01',
      unlockedHardcoreAt: undefined,
    });
    const unlockedHardcore = createAchievement({
      unlockedAt: undefined,
      unlockedHardcoreAt: '2024-01-01',
    });
    const unlockedBoth = createAchievement({
      unlockedAt: '2024-01-01',
      unlockedHardcoreAt: '2024-01-02',
    });

    const achievements = [
      lockedAchievement1,
      unlockedSoftcore,
      lockedAchievement2,
      unlockedHardcore,
      unlockedBoth,
    ];
    const filters = { showLockedOnly: true, showMissableOnly: false };

    // ACT
    const result = filterAchievements(achievements, filters);

    // ASSERT
    expect(result).toEqual([lockedAchievement1, lockedAchievement2]);
  });

  it('given showMissableOnly is true, only returns achievements with type "missable"', () => {
    // ARRANGE
    const missableAchievement1 = createAchievement({ type: 'missable' });
    const missableAchievement2 = createAchievement({ type: 'missable' });
    const progressionAchievement = createAchievement({ type: 'progression' });
    const winConditionAchievement = createAchievement({ type: 'win_condition' });

    const achievements = [
      missableAchievement1,
      progressionAchievement,
      missableAchievement2,
      winConditionAchievement,
    ];
    const filters = { showLockedOnly: false, showMissableOnly: true };

    // ACT
    const result = filterAchievements(achievements, filters);

    // ASSERT
    expect(result).toEqual([missableAchievement1, missableAchievement2]);
  });

  it('given both filters are active, only returns locked missable achievements', () => {
    // ARRANGE
    const lockedMissable = createAchievement({
      type: 'missable',
      unlockedAt: undefined,
      unlockedHardcoreAt: undefined,
    });
    const unlockedMissable = createAchievement({
      type: 'missable',
      unlockedAt: '2024-01-01',
      unlockedHardcoreAt: undefined,
    });
    const lockedProgression = createAchievement({
      type: 'progression',
      unlockedAt: undefined,
      unlockedHardcoreAt: undefined,
    });
    const unlockedProgression = createAchievement({
      type: 'progression',
      unlockedAt: '2024-01-01',
      unlockedHardcoreAt: undefined,
    });

    const achievements = [lockedMissable, unlockedMissable, lockedProgression, unlockedProgression];
    const filters = { showLockedOnly: true, showMissableOnly: true };

    // ACT
    const result = filterAchievements(achievements, filters);

    // ASSERT
    expect(result).toEqual([lockedMissable]);
  });

  it('given achievements with undefined type, treats them as non-missable', () => {
    // ARRANGE
    const undefinedTypeAchievement = createAchievement({ type: undefined });
    const missableAchievement = createAchievement({ type: 'missable' });

    const achievements = [undefinedTypeAchievement, missableAchievement];
    const filters = { showLockedOnly: false, showMissableOnly: true };

    // ACT
    const result = filterAchievements(achievements, filters);

    // ASSERT
    expect(result).toEqual([missableAchievement]);
  });
});
