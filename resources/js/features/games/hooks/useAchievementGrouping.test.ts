import { renderHook } from '@/test';
import { createAchievement, createAchievementSetGroup } from '@/test/factories';

import { useAchievementGrouping } from './useAchievementGrouping';

describe('Hook: useAchievementGrouping', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() =>
      useAchievementGrouping({
        allAchievements: [],
        ssrLimitedAchievements: [],
      }),
    );

    // ASSERT
    expect(result.current).toBeTruthy();
  });

  it('given no achievement groups are provided, returns the correct values', () => {
    // ARRANGE
    const achievements = [createAchievement(), createAchievement()];

    const { result } = renderHook(() =>
      useAchievementGrouping({
        allAchievements: achievements,
        ssrLimitedAchievements: achievements,
      }),
    );

    // ASSERT
    expect(result.current.hasGroups).toEqual(false);
    expect(result.current.achievementGroups).toEqual([]);
    expect(result.current.bucketedAchievements).toBeNull();
    expect(result.current.ungroupedAchievementCount).toEqual(0);
  });

  it('given an empty rawAchievementGroups array is provided, returns hasGroups as false', () => {
    // ARRANGE
    const achievements = [createAchievement()];

    const { result } = renderHook(() =>
      useAchievementGrouping({
        allAchievements: achievements,
        ssrLimitedAchievements: achievements,
        rawAchievementGroups: [], // !! explicitly empty
      }),
    );

    // ASSERT
    expect(result.current.hasGroups).toEqual(false);
  });

  it('given achievement groups are provided, returns hasGroups as true', () => {
    // ARRANGE
    const achievements = [createAchievement(), createAchievement()];
    const groups = [createAchievementSetGroup({ achievementCount: 1 })];

    const { result } = renderHook(() =>
      useAchievementGrouping({
        allAchievements: achievements,
        ssrLimitedAchievements: achievements,
        rawAchievementGroups: groups,
      }),
    );

    // ASSERT
    expect(result.current.hasGroups).toEqual(true);
  });

  it('given achievement groups are provided, returns the groups in achievementGroups', () => {
    // ARRANGE
    const achievements = [createAchievement(), createAchievement()];
    const groups = [
      createAchievementSetGroup({ achievementCount: 1 }),
      createAchievementSetGroup({ achievementCount: 1 }),
    ];

    const { result } = renderHook(() =>
      useAchievementGrouping({
        allAchievements: achievements,
        ssrLimitedAchievements: achievements,
        rawAchievementGroups: groups,
      }),
    );

    // ASSERT
    expect(result.current.achievementGroups).toEqual(groups);
  });

  it('given achievement groups are provided, returns bucketedAchievements as non-null', () => {
    // ARRANGE
    const achievements = [createAchievement(), createAchievement()];
    const groups = [createAchievementSetGroup({ achievementCount: 2 })];

    const { result } = renderHook(() =>
      useAchievementGrouping({
        allAchievements: achievements,
        ssrLimitedAchievements: achievements,
        rawAchievementGroups: groups,
      }),
    );

    // ASSERT
    expect(result.current.bucketedAchievements).not.toBeNull();
  });

  it('given some achievements are not in any group, correctly calculates ungroupedAchievementCount', () => {
    // ARRANGE
    const achievements = [
      createAchievement(),
      createAchievement(),
      createAchievement(),
      createAchievement(),
      createAchievement(),
    ];
    const groups = [
      createAchievementSetGroup({ achievementCount: 2 }),
      createAchievementSetGroup({ achievementCount: 1 }),
    ];

    const { result } = renderHook(() =>
      useAchievementGrouping({
        allAchievements: achievements,
        ssrLimitedAchievements: achievements,
        rawAchievementGroups: groups,
      }),
    );

    // ASSERT
    // ... 5 total achievements - (2 + 1) in groups = 2 ungrouped ...
    expect(result.current.ungroupedAchievementCount).toEqual(2);
  });

  it('given all achievements are in groups, returns zero for ungroupedAchievementCount', () => {
    // ARRANGE
    const achievements = [createAchievement(), createAchievement(), createAchievement()];
    const groups = [
      createAchievementSetGroup({ achievementCount: 2 }),
      createAchievementSetGroup({ achievementCount: 1 }),
    ];

    const { result } = renderHook(() =>
      useAchievementGrouping({
        allAchievements: achievements,
        ssrLimitedAchievements: achievements,
        rawAchievementGroups: groups,
      }),
    );

    // ASSERT
    expect(result.current.ungroupedAchievementCount).toEqual(0);
  });

  it('given SSR-limited achievements differ from all achievements, uses allAchievements length for ungrouped count calculation', () => {
    // ARRANGE
    const allAchievements = [
      createAchievement(),
      createAchievement(),
      createAchievement(),
      createAchievement(),
    ];
    const ssrLimitedAchievements = [allAchievements[0], allAchievements[1]]; // !! only 2 of 4
    const groups = [createAchievementSetGroup({ achievementCount: 1 })];

    const { result } = renderHook(() =>
      useAchievementGrouping({
        allAchievements,
        ssrLimitedAchievements,
        rawAchievementGroups: groups,
      }),
    );

    // ASSERT
    // ... should use allAchievements.length (4) - group count (1) = 3 ...
    expect(result.current.ungroupedAchievementCount).toEqual(3);
  });

  it('given multiple groups with varying achievement counts, correctly sums group counts for ungrouped calculation', () => {
    // ARRANGE
    const achievements = Array.from({ length: 10 }, () => createAchievement());
    const groups = [
      createAchievementSetGroup({ achievementCount: 3 }),
      createAchievementSetGroup({ achievementCount: 2 }),
      createAchievementSetGroup({ achievementCount: 4 }),
    ];

    const { result } = renderHook(() =>
      useAchievementGrouping({
        allAchievements: achievements,
        ssrLimitedAchievements: achievements,
        rawAchievementGroups: groups,
      }),
    );

    // ASSERT
    // ... 10 total - (3 + 2 + 4) = 1 ungrouped ...
    expect(result.current.ungroupedAchievementCount).toEqual(1);
  });
});
