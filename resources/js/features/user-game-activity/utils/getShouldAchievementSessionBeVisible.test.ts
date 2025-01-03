import {
  createAchievement,
  createPlayerGameActivityEvent,
  createPlayerGameActivitySession,
} from '@/test/factories';

import { getShouldAchievementSessionBeVisible } from './getShouldAchievementSessionBeVisible';

describe('Util: getShouldAchievementSessionBeVisible', () => {
  it('is defined', () => {
    // ASSERT
    expect(getShouldAchievementSessionBeVisible).toBeDefined();
  });

  it('given some events in the session are for achievement unlocks and only achievement sessions should be visible, returns true', () => {
    // ARRANGE
    const session = createPlayerGameActivitySession({
      events: [createPlayerGameActivityEvent({ achievement: createAchievement() })],
    });

    // ACT
    const result = getShouldAchievementSessionBeVisible(session, true);

    // ASSERT
    expect(result).toEqual(true);
  });

  it('given some events in the session are for achievement unlocks and all sessions should be visible, returns true', () => {
    // ARRANGE
    const session = createPlayerGameActivitySession({
      events: [createPlayerGameActivityEvent({ achievement: createAchievement() })],
    });

    // ACT
    const result = getShouldAchievementSessionBeVisible(session, false);

    // ASSERT
    expect(result).toEqual(true);
  });

  it('given no events in the session are for achievement unlocks and only achievement sessions should be visible, returns false', () => {
    // ARRANGE
    const session = createPlayerGameActivitySession({
      events: [createPlayerGameActivityEvent({ achievement: null })],
    });

    // ACT
    const result = getShouldAchievementSessionBeVisible(session, true);

    // ASSERT
    expect(result).toEqual(false);
  });
});
