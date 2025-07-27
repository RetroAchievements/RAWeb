import {
  createAchievement,
  createAchievementSet,
  createGameAchievementSet,
} from '@/test/factories';

import { getAllPageAchievements } from './getAllPageAchievements';

describe('Util: getAllPageAchievements', () => {
  it('is defined', () => {
    // ASSERT
    expect(getAllPageAchievements).toBeDefined();
  });

  it('given an empty array, returns an empty array', () => {
    // ARRANGE
    const gameAchievementSets: App.Platform.Data.GameAchievementSet[] = [];

    // ACT
    const result = getAllPageAchievements(gameAchievementSets);

    // ASSERT
    expect(result).toEqual([]);
  });

  it('given no target achievement set ID, returns all achievements from all sets', () => {
    // ARRANGE
    const achievements1 = [createAchievement(), createAchievement()];
    const achievements2 = [createAchievement(), createAchievement(), createAchievement()];

    const gameAchievementSets = [
      createGameAchievementSet({
        achievementSet: createAchievementSet({ achievements: achievements1 }),
      }),
      createGameAchievementSet({
        achievementSet: createAchievementSet({ achievements: achievements2 }),
      }),
    ];

    // ACT
    const result = getAllPageAchievements(gameAchievementSets);

    // ASSERT
    expect(result).toHaveLength(5);
    expect(result).toEqual([...achievements1, ...achievements2]);
  });

  it('given a target achievement set ID, only returns achievements from that set', () => {
    // ARRANGE
    const targetSetAchievements = [createAchievement(), createAchievement()];
    const otherSetAchievements = [createAchievement(), createAchievement(), createAchievement()];

    const gameAchievementSets = [
      createGameAchievementSet({
        achievementSet: createAchievementSet({
          id: 123, // !! target set ID
          achievements: targetSetAchievements,
        }),
      }),
      createGameAchievementSet({
        achievementSet: createAchievementSet({
          id: 456,
          achievements: otherSetAchievements,
        }),
      }),
    ];

    // ACT
    const result = getAllPageAchievements(gameAchievementSets, 123);

    // ASSERT
    expect(result).toHaveLength(2);
    expect(result).toEqual(targetSetAchievements);
  });

  it('given a target achievement set ID that does not exist, returns an empty array', () => {
    // ARRANGE
    const gameAchievementSets = [
      createGameAchievementSet({
        achievementSet: createAchievementSet({
          id: 123,
          achievements: [createAchievement()],
        }),
      }),
      createGameAchievementSet({
        achievementSet: createAchievementSet({
          id: 456,
          achievements: [createAchievement()],
        }),
      }),
    ];

    // ACT
    const result = getAllPageAchievements(gameAchievementSets, 999); // !! non-existent ID

    // ASSERT
    expect(result).toEqual([]);
  });

  it('given null as target achievement set ID, returns all achievements', () => {
    // ARRANGE
    const achievements1 = [createAchievement()];
    const achievements2 = [createAchievement()];

    const gameAchievementSets = [
      createGameAchievementSet({
        achievementSet: createAchievementSet({ achievements: achievements1 }),
      }),
      createGameAchievementSet({
        achievementSet: createAchievementSet({ achievements: achievements2 }),
      }),
    ];

    // ACT
    const result = getAllPageAchievements(gameAchievementSets, null);

    // ASSERT
    expect(result).toHaveLength(2);
    expect(result).toEqual([...achievements1, ...achievements2]);
  });
});
