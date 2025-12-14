import { createAchievement, createAchievementSetGroup } from '@/test/factories';

import { UNGROUPED_BUCKET_ID } from './UNGROUPED_BUCKET_ID';
import { bucketAchievementsByGroup } from './bucketAchievementsByGroup';

describe('Util: bucketAchievementsByGroup', () => {
  it('is defined', () => {
    // ASSERT
    expect(bucketAchievementsByGroup).toBeDefined();
  });

  it('given no achievements and no groups, returns only the ungrouped bucket', () => {
    // ACT
    const result = bucketAchievementsByGroup([], []);

    // ASSERT
    expect(result).toEqual({ [UNGROUPED_BUCKET_ID]: [] });
  });

  it('given achievements with no groups set, places everything in the ungrouped bucket', () => {
    // ARRANGE
    const achievements = [
      createAchievement({ id: 1, groupId: null }),
      createAchievement({ id: 2, groupId: undefined }),
      createAchievement({ id: 3, groupId: null }),
    ];

    // ACT
    const result = bucketAchievementsByGroup(achievements, []);

    // ASSERT
    expect(result[UNGROUPED_BUCKET_ID]).toHaveLength(3);
    expect(result[UNGROUPED_BUCKET_ID].map((a) => a.id)).toEqual([1, 2, 3]);
  });

  it('given achievements and groups, correctly places achievements into the right buckets', () => {
    // ARRANGE
    const groups = [
      createAchievementSetGroup({ id: 1, label: 'FF1', orderColumn: 0 }),
      createAchievementSetGroup({ id: 2, label: 'FF2', orderColumn: 1 }),
    ];

    const achievements = [
      createAchievement({ id: 101, groupId: 1 }),
      createAchievement({ id: 102, groupId: 1 }),
      createAchievement({ id: 201, groupId: 2 }),
      createAchievement({ id: 301, groupId: null }), // ungrouped
    ];

    // ACT
    const result = bucketAchievementsByGroup(achievements, groups);

    // ASSERT
    expect(result[1]).toHaveLength(2);
    expect(result[1].map((a) => a.id)).toEqual([101, 102]);

    expect(result[2]).toHaveLength(1);
    expect(result[2].map((a) => a.id)).toEqual([201]);

    expect(result[UNGROUPED_BUCKET_ID]).toHaveLength(1);
    expect(result[UNGROUPED_BUCKET_ID].map((a) => a.id)).toEqual([301]);
  });

  it('given an achievement with an unknown group ID, places it in the ungrouped bucket', () => {
    // ARRANGE
    const groups = [createAchievementSetGroup({ id: 1, label: 'FF1', orderColumn: 0 })];

    const achievements = [
      createAchievement({ id: 101, groupId: 1 }),
      createAchievement({ id: 999, groupId: 999 }), // !! unknown
    ];

    // ACT
    const result = bucketAchievementsByGroup(achievements, groups);

    // ASSERT
    expect(result[1]).toHaveLength(1);
    expect(result[UNGROUPED_BUCKET_ID]).toHaveLength(1);
    expect(result[UNGROUPED_BUCKET_ID][0].id).toEqual(999);
  });

  it('initializes empty arrays for groups with no achievements', () => {
    // ARRANGE
    const groups = [
      createAchievementSetGroup({ id: 1, label: 'FF1', orderColumn: 0 }),
      createAchievementSetGroup({ id: 2, label: 'FF2', orderColumn: 1 }),
    ];

    const achievements = [createAchievement({ id: 101, groupId: 1 })];

    // ACT
    const result = bucketAchievementsByGroup(achievements, groups);

    // ASSERT
    expect(result[1]).toHaveLength(1);
    expect(result[2]).toHaveLength(0);
    expect(result[UNGROUPED_BUCKET_ID]).toHaveLength(0);
  });
});
