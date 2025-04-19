import dayjs from 'dayjs';

import { createAchievement } from '@/test/factories';

import { sortAchievements } from './sortAchievements';

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
        activeFrom: dayjs().subtract(1, 'second').toISOString(),
        activeThrough: dayjs().add(1, 'second').toISOString(),
        activeUntil: dayjs().add(1, 'second').add(1, 'day').toISOString(),
        isObfuscated: false,
      },
      {
        achievement: achievements[2],
        activeFrom: dayjs().subtract(2, 'second').toISOString(),
        activeThrough: dayjs().subtract(1, 'second').toISOString(),
        activeUntil: dayjs().subtract(1, 'second').add(1, 'day').toISOString(),
        isObfuscated: false,
      },
      {
        achievement: achievements[3],
        activeFrom: dayjs().add(1, 'second').toISOString(),
        activeThrough: dayjs().add(10, 'day').toISOString(),
        activeUntil: dayjs().add(11, 'day').toISOString(),
        isObfuscated: false,
      },
      {
        achievement: achievements[4],
        activeFrom: dayjs().add(40, 'day').toISOString(),
        activeThrough: dayjs().add(50, 'day').toISOString(),
        activeUntil: dayjs().add(51, 'day').toISOString(),
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
    const baseAchievement = createAchievement({
      id: 1,
      createdAt: '2023-01-01',
      orderColumn: 1,
    });

    const achievements: App.Platform.Data.Achievement[] = [
      { ...baseAchievement, id: 1, createdAt: '2023-01-02', orderColumn: 2 },
      { ...baseAchievement, id: 2, createdAt: '2023-01-02', orderColumn: 1 }, // Same date, different order
      { ...baseAchievement, id: 3, createdAt: '2023-01-01', orderColumn: 1 }, // Earlier date
    ];

    const eventAchievements: App.Platform.Data.EventAchievement[] = [
      // Make all achievements active to test secondary sort criteria
      {
        achievement: achievements[0],
        activeFrom: dayjs().subtract(1, 'second').toISOString(),
        activeThrough: dayjs().add(1, 'second').toISOString(),
        activeUntil: dayjs().add(1, 'second').add(1, 'day').toISOString(),
        isObfuscated: false,
      },
      {
        achievement: achievements[1],
        activeFrom: dayjs().subtract(1, 'second').toISOString(),
        activeThrough: dayjs().add(1, 'second').toISOString(),
        activeUntil: dayjs().add(1, 'second').add(1, 'day').toISOString(),
        isObfuscated: false,
      },
      {
        achievement: achievements[2],
        activeFrom: dayjs().subtract(1, 'second').toISOString(),
        activeThrough: dayjs().add(1, 'second').toISOString(),
        activeUntil: dayjs().add(1, 'second').add(1, 'day').toISOString(),
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
