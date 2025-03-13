import { createAchievement, createEventAchievement } from '@/test/factories';

import { getStatus, sortAchievements } from './sortAchievements';

describe('Util: sortAchievements', () => {
  it('given displayOrder sort, sorts by orderColumn ascending', () => {
    // ARRANGE
    const baseAchievement = createAchievement({
      id: 1,
      createdAt: '2023-01-01',
      orderColumn: 1,
    });

    const achievements: App.Platform.Data.Achievement[] = [
      { ...baseAchievement, id: 1, orderColumn: 2 },
      { ...baseAchievement, id: 2, orderColumn: 1 },
    ];

    // ACT
    const result = sortAchievements(achievements, 'displayOrder');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([2, 1]);
  });

  it('given -displayOrder sort, sorts by orderColumn descending', () => {
    // ARRANGE
    const baseAchievement = createAchievement({
      id: 1,
      createdAt: '2023-01-01',
      orderColumn: 1,
    });

    const achievements: App.Platform.Data.Achievement[] = [
      { ...baseAchievement, id: 1, orderColumn: 1 },
      { ...baseAchievement, id: 2, orderColumn: 2 },
    ];

    // ACT
    const result = sortAchievements(achievements, '-displayOrder');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([2, 1]);
  });

  it('given achievements with same orderColumn, sorts by createdAt as secondary criteria', () => {
    // ARRANGE
    const baseAchievement = createAchievement({
      id: 1,
      createdAt: '2023-01-01',
      orderColumn: 1,
    });

    const achievements: App.Platform.Data.Achievement[] = [
      { ...baseAchievement, id: 1, orderColumn: 1, createdAt: '2023-01-02' },
      { ...baseAchievement, id: 2, orderColumn: 1, createdAt: '2023-01-01' },
    ];

    // ACT
    const result = sortAchievements(achievements, 'displayOrder');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([2, 1]);
  });

  it('given wonBy sort, sorts by unlocksHardcoreTotal descending', () => {
    // ARRANGE
    const baseAchievement = createAchievement({
      id: 1,
      createdAt: '2023-01-01',
      orderColumn: 1,
    });

    const achievements: App.Platform.Data.Achievement[] = [
      { ...baseAchievement, id: 1, unlocksHardcoreTotal: 10 },
      { ...baseAchievement, id: 2, unlocksHardcoreTotal: 20 },
    ];

    // ACT
    const result = sortAchievements(achievements, 'wonBy');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([2, 1]);
  });

  it('given achievements with same unlocksHardcoreTotal, sorts by orderColumn as secondary criteria', () => {
    // ARRANGE
    const baseAchievement = createAchievement({
      id: 1,
      createdAt: '2023-01-01',
      orderColumn: 1,
    });

    const achievements: App.Platform.Data.Achievement[] = [
      { ...baseAchievement, id: 1, unlocksHardcoreTotal: 10, orderColumn: 2 },
      { ...baseAchievement, id: 2, unlocksHardcoreTotal: 10, orderColumn: 1 },
    ];

    // ACT
    const result = sortAchievements(achievements, 'wonBy');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([1, 2]);
  });

  it('given active sort, sorts by status priority and then dates', () => {
    // ARRANGE
    const baseAchievement = createAchievement({
      id: 1,
      createdAt: '2023-01-01',
      orderColumn: 1,
    });

    const now = new Date();
    const achievements: App.Platform.Data.Achievement[] = [
      { ...baseAchievement, id: 1 }, // Evergreen
      { ...baseAchievement, id: 2 }, // Active
      { ...baseAchievement, id: 3 }, // Expired
      { ...baseAchievement, id: 4 }, // Upcoming (within 30 days)
      { ...baseAchievement, id: 5 }, // Future (more than 30 days away)
    ];

    const eventAchievements: App.Platform.Data.EventAchievement[] = [
      {
        achievement: achievements[1],
        activeFrom: new Date(now.getTime() - 1000).toISOString(),
        activeUntil: new Date(now.getTime() + 1000).toISOString(),
        isObfuscated: false,
      },
      {
        achievement: achievements[2],
        activeFrom: new Date(now.getTime() - 2000).toISOString(),
        activeUntil: new Date(now.getTime() - 1000).toISOString(),
        isObfuscated: false,
      },
      {
        achievement: achievements[3],
        activeFrom: new Date(now.getTime() + 1000).toISOString(),
        activeUntil: new Date(now.getTime() + 1000 * 60 * 60 * 24 * 10).toISOString(), // 10 days from now
        isObfuscated: false,
      },
      {
        achievement: achievements[4],
        activeFrom: new Date(now.getTime() + 1000 * 60 * 60 * 24 * 40).toISOString(), // 40 days from now
        activeUntil: new Date(now.getTime() + 1000 * 60 * 60 * 24 * 50).toISOString(), // 50 days from now
        isObfuscated: false,
      },
    ];

    // ACT
    const result = sortAchievements(achievements, 'active', eventAchievements);

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([2, 3, 4, 5, 1]);
  });

  it('given an unsupported sort order, returns achievements unmodified', () => {
    // ARRANGE
    const baseAchievement = createAchievement({
      id: 1,
      createdAt: '2023-01-01',
      orderColumn: 1,
    });

    const achievements: App.Platform.Data.Achievement[] = [
      { ...baseAchievement, id: 1 },
      { ...baseAchievement, id: 2 },
    ];

    // ACT
    const result = sortAchievements(achievements, 'invalid' as any);

    // ASSERT
    expect(result).toEqual(achievements);
  });

  it('given active sort with same status, sorts by createdAt and then orderColumn', () => {
    // ARRANGE
    const now = new Date();
    const baseAchievement = createAchievement({
      id: 1,
      createdAt: '2023-01-01',
      orderColumn: 1,
    });

    const achievements: App.Platform.Data.Achievement[] = [
      { ...baseAchievement, id: 1, createdAt: '2023-01-02', orderColumn: 2 },
      { ...baseAchievement, id: 2, createdAt: '2023-01-02', orderColumn: 1 }, // !! same date, different order
      { ...baseAchievement, id: 3, createdAt: '2023-01-01', orderColumn: 1 }, // !! earlier date
    ];

    const eventAchievements: App.Platform.Data.EventAchievement[] = [
      // Make all achievements active to test secondary sort criteria
      {
        achievement: achievements[0],
        activeFrom: new Date(now.getTime() - 1000).toISOString(),
        activeUntil: new Date(now.getTime() + 1000).toISOString(),
        isObfuscated: false,
      },
      {
        achievement: achievements[1],
        activeFrom: new Date(now.getTime() - 1000).toISOString(),
        activeUntil: new Date(now.getTime() + 1000).toISOString(),
        isObfuscated: false,
      },
      {
        achievement: achievements[2],
        activeFrom: new Date(now.getTime() - 1000).toISOString(),
        activeUntil: new Date(now.getTime() + 1000).toISOString(),
        isObfuscated: false,
      },
    ];

    // ACT
    const result = sortAchievements(achievements, 'active', eventAchievements);

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([3, 2, 1]);
  });

  it('given -wonBy sort, sorts by unlocksHardcoreTotal ascending', () => {
    // ARRANGE
    const baseAchievement = createAchievement({
      id: 1,
      createdAt: '2023-01-01',
      orderColumn: 1,
    });

    const achievements: App.Platform.Data.Achievement[] = [
      { ...baseAchievement, id: 1, unlocksHardcoreTotal: 10 },
      { ...baseAchievement, id: 2, unlocksHardcoreTotal: 20 },
    ];

    // ACT
    const result = sortAchievements(achievements, '-wonBy');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([1, 2]);
  });

  it('given wonBy sort with missing values, handles null/undefined gracefully', () => {
    // ARRANGE
    const baseAchievement = createAchievement({
      id: 1,
      createdAt: '2023-01-01',
      orderColumn: 1,
    });

    const achievements: App.Platform.Data.Achievement[] = [
      { ...baseAchievement, id: 1, unlocksHardcoreTotal: undefined, orderColumn: undefined },
      { ...baseAchievement, id: 2, unlocksHardcoreTotal: undefined, orderColumn: undefined },
      { ...baseAchievement, id: 3, unlocksHardcoreTotal: 10, orderColumn: 1 },
    ];

    // ACT
    const result = sortAchievements(achievements, 'wonBy');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([3, 1, 2]);
  });
});

describe('Util: getStatus', () => {
  it('given an achievement with no event data, returns evergreen status', () => {
    // ARRANGE
    const achievement = createAchievement({
      id: 1,
      createdAt: '2023-01-01',
    });

    // ACT
    const result = getStatus(achievement, []);

    // ASSERT
    expect(result).toEqual(4);
  });

  it('given an achievement that is currently active, returns active status', () => {
    // ARRANGE
    const now = new Date();
    const achievement = createAchievement({ id: 1 });
    const eventAchievements = [
      createEventAchievement({
        achievement,
        activeFrom: new Date(now.getTime() - 1000).toISOString(), // !! 1 second ago
        activeUntil: new Date(now.getTime() + 1000).toISOString(), // !! 1 second from now
      }),
    ];

    // ACT
    const result = getStatus(achievement, eventAchievements);

    // ASSERT
    expect(result).toEqual(0);
  });

  it('given an achievement that has expired, returns expired status', () => {
    // ARRANGE
    const now = new Date();
    const achievement = createAchievement({ id: 1 });
    const eventAchievements: App.Platform.Data.EventAchievement[] = [
      createEventAchievement({
        achievement,
        activeFrom: new Date(now.getTime() - 2000).toISOString(), // !! 2 seconds ago
        activeUntil: new Date(now.getTime() - 1000).toISOString(), // !! 1 second ago
      }),
    ];

    // ACT
    const result = getStatus(achievement, eventAchievements);

    // ASSERT
    expect(result).toEqual(1);
  });

  it('given an achievement that is upcoming within 30 days, returns upcoming status', () => {
    // ARRANGE
    const now = new Date();
    const achievement = createAchievement({ id: 1 });
    const eventAchievements = [
      createEventAchievement({
        achievement,
        activeFrom: new Date(now.getTime() + 1000 * 60 * 60 * 24 * 15).toISOString(), // !! 15 days from now
        activeUntil: new Date(now.getTime() + 1000 * 60 * 60 * 24 * 20).toISOString(), // !! 20 days from now
      }),
    ];

    // ACT
    const result = getStatus(achievement, eventAchievements);

    // ASSERT
    expect(result).toEqual(2);
  });

  it('given an achievement that is upcoming but more than 30 days away, returns future status', () => {
    // ARRANGE
    const now = new Date();
    const achievement = createAchievement({ id: 1 });
    const eventAchievements = [
      createEventAchievement({
        achievement,
        activeFrom: new Date(now.getTime() + 1000 * 60 * 60 * 24 * 40).toISOString(), // !! 40 days from now
        activeUntil: new Date(now.getTime() + 1000 * 60 * 60 * 24 * 45).toISOString(), // !! 45 days from now
      }),
    ];

    // ACT
    const result = getStatus(achievement, eventAchievements);

    // ASSERT
    expect(result).toEqual(3);
  });
});
