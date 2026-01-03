import { UNGROUPED_BUCKET_ID } from './UNGROUPED_BUCKET_ID';

export function bucketAchievementsByGroup(
  achievements: App.Platform.Data.Achievement[],
  groups: App.Platform.Data.AchievementSetGroup[],
): Record<number, App.Platform.Data.Achievement[]> {
  const result: Record<number, App.Platform.Data.Achievement[]> = {};

  // Initialize each of the group buckets.
  for (const group of groups) {
    result[group.id] = [];
  }

  result[UNGROUPED_BUCKET_ID] = [];

  // Place achievements into their respective buckets.
  for (const achievement of achievements) {
    const groupId = achievement.groupId ?? UNGROUPED_BUCKET_ID;

    if (result[groupId]) {
      result[groupId].push(achievement);
    } else {
      // If the groupId doesn't match any known group, treat it as ungrouped.
      result[UNGROUPED_BUCKET_ID].push(achievement);
    }
  }

  return result;
}
