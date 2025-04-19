import dayjs from 'dayjs';

import { createAchievement } from '@/test/factories';

import { sortAchievements } from './sortAchievements';

describe('Util: sortAchievements', () => {
  it('is defined', () => {
    // ASSERT
    expect(sortAchievements).toBeDefined();
  });

  describe('normal sort', () => {
    it('given unlockedAt dates, prioritizes unlocked achievements first', () => {
      // ARRANGE
      const baseAchievement = createAchievement();
      const achievements = [
        { ...baseAchievement, id: 1 },
        { ...baseAchievement, id: 2, unlockedAt: '2023-01-01' },
      ];

      // ACT
      const result = sortAchievements(achievements, 'normal');

      // ASSERT
      expect(result.map((a) => a.id)).toEqual([2, 1]);
    });

    it('given unlockedHardcoreAt dates, prioritizes unlocked achievements first', () => {
      // ARRANGE
      const baseAchievement = createAchievement();
      const achievements = [
        { ...baseAchievement, id: 1 },
        { ...baseAchievement, id: 2, unlockedHardcoreAt: '2023-01-01' },
      ];

      // ACT
      const result = sortAchievements(achievements, 'normal');

      // ASSERT
      expect(result.map((a) => a.id)).toEqual([2, 1]);
    });

    it('given same unlock status, sorts by orderColumn ascending', () => {
      // ARRANGE
      const baseAchievement = createAchievement();
      const achievements = [
        { ...baseAchievement, id: 1, orderColumn: 2 },
        { ...baseAchievement, id: 2, orderColumn: 1 },
      ];

      // ACT
      const result = sortAchievements(achievements, 'normal');

      // ASSERT
      expect(result.map((a) => a.id)).toEqual([2, 1]);
    });

    it('given same orderColumn, sorts by createdAt ascending', () => {
      // ARRANGE
      const baseAchievement = createAchievement();
      const achievements = [
        { ...baseAchievement, id: 1, orderColumn: 1, createdAt: '2023-02-01' },
        { ...baseAchievement, id: 2, orderColumn: 1, createdAt: '2023-01-01' },
      ];

      // ACT
      const result = sortAchievements(achievements, 'normal');

      // ASSERT
      expect(result.map((a) => a.id)).toEqual([2, 1]);
    });

    it('given missing orderColumn or createdAt values, handles them gracefully', () => {
      // ARRANGE
      const baseAchievement = createAchievement();
      const achievements = [
        { ...baseAchievement, id: 1, orderColumn: undefined, createdAt: undefined },
        { ...baseAchievement, id: 2, orderColumn: 1, createdAt: '2023-01-01' },
      ];

      // ACT
      const result = sortAchievements(achievements, 'normal');

      // ASSERT
      expect(result.map((a) => a.id)).toEqual([1, 2]);
    });
  });

  it('given -normal sort, reverses order while maintaining unlock priority', () => {
    // ARRANGE
    const baseAchievement = createAchievement();
    const achievements = [
      { ...baseAchievement, id: 1, orderColumn: 1 },
      { ...baseAchievement, id: 2, orderColumn: 2 },
      { ...baseAchievement, id: 3, unlockedAt: '2023-01-01', orderColumn: 1 },
      { ...baseAchievement, id: 4, unlockedAt: '2023-01-01', orderColumn: 2 },
    ];

    // ACT
    const result = sortAchievements(achievements, '-normal');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([2, 1, 4, 3]);
  });

  it('given points sort, sorts by points ascending while keeping unlocked first', () => {
    // ARRANGE
    const baseAchievement = createAchievement();
    const achievements = [
      { ...baseAchievement, id: 1, points: 20 },
      { ...baseAchievement, id: 2, points: 10 },
      { ...baseAchievement, id: 3, points: 30, unlockedAt: '2023-01-01' },
      { ...baseAchievement, id: 4, points: 5, unlockedAt: '2023-01-01' },
    ];

    // ACT
    const result = sortAchievements(achievements, 'points');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([3, 4, 1, 2]);
  });

  it('given -points sort, sorts by points descending while keeping unlocked first', () => {
    // ARRANGE
    const baseAchievement = createAchievement();
    const achievements = [
      { ...baseAchievement, id: 1, points: 20 },
      { ...baseAchievement, id: 2, points: 10 },
      { ...baseAchievement, id: 3, points: 30, unlockedAt: '2023-01-01' },
      { ...baseAchievement, id: 4, points: 5, unlockedAt: '2023-01-01' },
    ];

    // ACT
    const result = sortAchievements(achievements, '-points');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([4, 3, 2, 1]);
  });

  it('given points sort and same points value, sorts by orderColumn as secondary criteria', () => {
    // ARRANGE
    const baseAchievement = createAchievement();
    const achievements = [
      { ...baseAchievement, id: 1, points: 10, orderColumn: 2 },
      { ...baseAchievement, id: 2, points: 10, orderColumn: 1 },
    ];

    // ACT
    const result = sortAchievements(achievements, 'points');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([1, 2]);
  });

  it('given title sort, sorts case-insensitively while keeping unlocked first', () => {
    // ARRANGE
    const baseAchievement = createAchievement();
    const achievements = [
      { ...baseAchievement, id: 1, title: 'Beta' },
      { ...baseAchievement, id: 2, title: 'alpha' },
      { ...baseAchievement, id: 3, title: 'Gamma', unlockedAt: '2023-01-01' },
    ];

    // ACT
    const result = sortAchievements(achievements, 'title');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([3, 2, 1]);
  });

  it('given -title sort, sorts case-insensitively in reverse while keeping unlocked first', () => {
    // ARRANGE
    const baseAchievement = createAchievement();
    const achievements = [
      { ...baseAchievement, id: 1, title: 'Beta' },
      { ...baseAchievement, id: 2, title: 'alpha' },
      { ...baseAchievement, id: 3, title: 'Gamma', unlockedAt: '2023-01-01' },
    ];

    // ACT
    const result = sortAchievements(achievements, '-title');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([3, 1, 2]);
  });

  it('given type sort, sorts according to type priority while keeping unlocked first', () => {
    // ARRANGE
    const baseAchievement = createAchievement();
    const achievements = [
      { ...baseAchievement, id: 1, type: 'missable' },
      { ...baseAchievement, id: 2, type: 'progression' },
      { ...baseAchievement, id: 3, type: 'win_condition' },
      { ...baseAchievement, id: 4, type: null },
      { ...baseAchievement, id: 5, type: 'unknown' },
      { ...baseAchievement, id: 6, type: 'progression', unlockedAt: '2023-01-01' },
    ];

    // ACT
    const result = sortAchievements(achievements as App.Platform.Data.Achievement[], 'type');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([6, 2, 3, 1, 4, 5]);
  });

  it('given -type sort, reverses type priority while keeping unlocked first', () => {
    // ARRANGE
    const baseAchievement = createAchievement();
    const achievements = [
      { ...baseAchievement, id: 1, type: 'missable' },
      { ...baseAchievement, id: 2, type: 'progression' },
      { ...baseAchievement, id: 3, type: 'win_condition' },
      { ...baseAchievement, id: 4, type: null },
      { ...baseAchievement, id: 5, type: 'unknown' },
      { ...baseAchievement, id: 6, type: 'progression', unlockedAt: '2023-01-01' },
    ];

    // ACT
    const result = sortAchievements(achievements as App.Platform.Data.Achievement[], '-type');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([6, 5, 4, 1, 3, 2]);
  });

  it('given type sort and same type, sorts by orderColumn and then id', () => {
    // ARRANGE
    const baseAchievement = createAchievement();
    const achievements = [
      { ...baseAchievement, id: 2, type: 'progression', orderColumn: 1 },
      { ...baseAchievement, id: 1, type: 'progression', orderColumn: 1 },
      { ...baseAchievement, id: 3, type: 'progression', orderColumn: 2 },
    ];

    // ACT
    const result = sortAchievements(achievements as App.Platform.Data.Achievement[], 'type');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([1, 2, 3]);
  });

  it('given displayOrder sort, sorts by orderColumn ascending', () => {
    // ARRANGE
    const baseAchievement = createAchievement();
    const achievements = [
      { ...baseAchievement, id: 1, orderColumn: 1 },
      { ...baseAchievement, id: 2, orderColumn: 2 },
      { ...baseAchievement, id: 3, orderColumn: 3 },
    ];

    // ACT
    const result = sortAchievements(achievements, 'displayOrder');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([1, 2, 3]);
  });

  it('given -displayOrder sort, sorts by orderColumn descending', () => {
    // ARRANGE
    const baseAchievement = createAchievement();
    const achievements = [
      { ...baseAchievement, id: 1, orderColumn: 1 },
      { ...baseAchievement, id: 2, orderColumn: 2 },
      { ...baseAchievement, id: 3, orderColumn: 3 },
    ];

    // ACT
    const result = sortAchievements(achievements, '-displayOrder');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([3, 2, 1]);
  });

  it('given wonBy sort, sorts by unlocksHardcoreTotal descending', () => {
    // ARRANGE
    const baseAchievement = createAchievement();
    const achievements = [
      { ...baseAchievement, id: 1, unlocksHardcoreTotal: 10 },
      { ...baseAchievement, id: 2, unlocksHardcoreTotal: 20 },
      { ...baseAchievement, id: 3, unlocksHardcoreTotal: 30 },
    ];

    // ACT
    const result = sortAchievements(achievements, 'wonBy');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([3, 2, 1]);
  });

  it('given -wonBy sort, sorts by unlocksHardcoreTotal ascending', () => {
    // ARRANGE
    const baseAchievement = createAchievement();
    const achievements = [
      { ...baseAchievement, id: 1, unlocksHardcoreTotal: 10 },
      { ...baseAchievement, id: 2, unlocksHardcoreTotal: 20 },
      { ...baseAchievement, id: 3, unlocksHardcoreTotal: 30 },
    ];

    // ACT
    const result = sortAchievements(achievements, '-wonBy');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([1, 2, 3]);
  });

  it('given active sort and no eventAchievements provided, treats achievements as evergreen', () => {
    // ARRANGE
    const baseAchievement = createAchievement();
    const achievements = [
      { ...baseAchievement, id: 1 },
      { ...baseAchievement, id: 2 },
    ];

    // ACT
    const result = sortAchievements(achievements, 'active');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([1, 2]);
  });

  it('given active sort, sorts by status priority and then dates', () => {
    // ARRANGE
    const baseAchievement = createAchievement();
    const achievements = [
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

  it('given an invalid sort order, returns achievements unmodified', () => {
    // ARRANGE
    const baseAchievement = createAchievement();
    const achievements = [
      { ...baseAchievement, id: 1 },
      { ...baseAchievement, id: 2 },
    ];

    // ACT
    const result = sortAchievements(achievements, 'invalid' as any);

    // ASSERT
    expect(result).toEqual(achievements);
  });

  it('given wonBy sort and achievements with same unlock status, sorts by unlocksHardcoreTotal', () => {
    // ARRANGE
    const baseAchievement = createAchievement();
    const achievements = [
      { ...baseAchievement, id: 1, unlockedAt: '2023-01-01', unlocksHardcoreTotal: 10 },
      { ...baseAchievement, id: 2, unlockedAt: '2023-01-02', unlocksHardcoreTotal: 20 },
    ];

    // ACT
    const result = sortAchievements(achievements, 'wonBy');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([2, 1]);
  });

  it('given active sort and same status but different creation dates, sorts by createdAt', () => {
    // ARRANGE
    const baseAchievement = createAchievement();
    const achievements = [
      { ...baseAchievement, id: 1, createdAt: '2023-02-01' },
      { ...baseAchievement, id: 2, createdAt: '2023-01-01' },
    ];

    const eventAchievements: App.Platform.Data.EventAchievement[] = [
      {
        achievement: achievements[0],
        activeFrom: dayjs().subtract(1, 'day').toISOString(),
        activeThrough: dayjs().add(1, 'day').toISOString(),
        activeUntil: dayjs().add(2, 'day').toISOString(),
        isObfuscated: false,
      },
      {
        achievement: achievements[1],
        activeFrom: dayjs().subtract(1, 'day').toISOString(),
        activeThrough: dayjs().add(1, 'day').toISOString(),
        activeUntil: dayjs().add(2, 'day').toISOString(),
        isObfuscated: false,
      },
    ];

    // ACT
    const result = sortAchievements(achievements, 'active', eventAchievements);

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([2, 1]);
  });

  it('given wonBy sort and different unlock status, prioritizes unlocked achievements regardless of unlocksHardcoreTotal', () => {
    // ARRANGE
    const baseAchievement = createAchievement();
    const achievements = [
      { ...baseAchievement, id: 1, unlocksHardcoreTotal: 100 }, // Not unlocked, higher count
      { ...baseAchievement, id: 2, unlockedAt: '2023-01-01', unlocksHardcoreTotal: 10 }, // Unlocked, lower count
    ];

    // ACT
    const result = sortAchievements(achievements, 'wonBy');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([2, 1]);
  });

  it('given wonBy sort and unlocksHardcoreTotal is the same, sorts by orderColumn', () => {
    // ARRANGE
    const baseAchievement = createAchievement();
    const achievements = [
      { ...baseAchievement, id: 1, unlocksHardcoreTotal: 10, orderColumn: 2 },
      { ...baseAchievement, id: 2, unlocksHardcoreTotal: 10, orderColumn: 1 },
    ];

    // ACT
    const result = sortAchievements(achievements, 'wonBy');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([1, 2]);
  });

  it('given displayOrder sort and same orderColumn, sorts by createdAt ascending', () => {
    // ARRANGE
    const baseAchievement = createAchievement();
    const achievements = [
      { ...baseAchievement, id: 1, orderColumn: 1, createdAt: '2023-02-01' },
      { ...baseAchievement, id: 2, orderColumn: 1, createdAt: '2023-01-01' },
    ];

    // ACT
    const result = sortAchievements(achievements, 'displayOrder');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([2, 1]);
  });

  it('given points sort and same points and orderColumn values, sorts by createdAt ascending', () => {
    // ARRANGE
    const baseAchievement = createAchievement();
    const achievements = [
      { ...baseAchievement, id: 1, points: 10, orderColumn: 1, createdAt: '2023-02-01' },
      { ...baseAchievement, id: 2, points: 10, orderColumn: 1, createdAt: '2023-01-01' },
    ];

    // ACT
    const result = sortAchievements(achievements, 'points');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([1, 2]);
  });

  it('given active sort and same status and creation dates, sorts by orderColumn', () => {
    // ARRANGE
    const baseAchievement = createAchievement();
    const sameDate = '2023-01-01';
    const achievements = [
      { ...baseAchievement, id: 1, orderColumn: 2, createdAt: sameDate },
      { ...baseAchievement, id: 2, orderColumn: 1, createdAt: sameDate },
    ];

    const eventAchievements: App.Platform.Data.EventAchievement[] = [
      {
        achievement: achievements[0],
        activeFrom: dayjs().subtract(1, 'day').toISOString(),
        activeThrough: dayjs().add(1, 'day').toISOString(),
        activeUntil: dayjs().add(2, 'day').toISOString(),
        isObfuscated: false,
      },
      {
        achievement: achievements[1],
        activeFrom: dayjs().subtract(1, 'day').toISOString(),
        activeThrough: dayjs().add(1, 'day').toISOString(),
        activeUntil: dayjs().add(2, 'day').toISOString(),
        isObfuscated: false,
      },
    ];

    // ACT
    const result = sortAchievements(achievements, 'active', eventAchievements);

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([2, 1]);
  });

  it('compareUnlockStatus prioritizes when first achievement is unlocked but second is not', () => {
    // ARRANGE
    const baseAchievement = createAchievement();
    const achievements = [
      { ...baseAchievement, id: 1, unlockedAt: '2023-01-01' }, // !! first is unlocked
      { ...baseAchievement, id: 2 }, // !! second is not unlocked
    ];

    // ACT
    const result = sortAchievements(achievements, 'normal');

    // ASSERT
    expect(result.map((a) => a.id)).toEqual([1, 2]);
  });
});
