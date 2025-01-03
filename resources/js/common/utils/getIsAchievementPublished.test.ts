import { createAchievement } from '@/test/factories';

import { getIsAchievementPublished } from './getIsAchievementPublished';

describe('Util: getIsAchievementPublished', () => {
  it('is defined', () => {
    // ASSERT
    expect(getIsAchievementPublished).toBeDefined();
  });

  it('given flags is 3, returns true', () => {
    // ARRANGE
    const achievement = createAchievement({ flags: 3 });

    // ACT
    const isAchievementPublished = getIsAchievementPublished(achievement);

    // ASSERT
    expect(isAchievementPublished).toEqual(true);
  });

  it('given flags is not 3, returns false', () => {
    // ARRANGE
    const achievement = createAchievement({ flags: 5 });

    // ACT
    const isAchievementPublished = getIsAchievementPublished(achievement);

    // ASSERT
    expect(isAchievementPublished).toEqual(false);
  });
});
