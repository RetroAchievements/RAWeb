import dayjs from 'dayjs';

import { createAchievement, createEventAchievement, createRaEvent } from '@/test/factories';

import { getEventAchievementTimeStatus } from './getEventAchievementTimeStatus';

describe('Util: getStatus', () => {
  it('given an achievement with no event data, returns evergreen status', () => {
    // ARRANGE
    const achievement = createAchievement({
      id: 1,
      createdAt: '2023-01-01',
    });

    // ACT
    const result = getEventAchievementTimeStatus(achievement, []);

    // ASSERT
    expect(result).toEqual(4);
  });

  it('given an achievement that is currently active, returns active status', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 1 });
    const evnt = createRaEvent({ activeFrom: null, activeThrough: null, activeUntil: null });
    const eventAchievements = [
      createEventAchievement({
        achievement,
        event: evnt,
        activeFrom: dayjs().subtract(1, 'second').toISOString(),
        activeThrough: dayjs().add(1, 'second').toISOString(),
        activeUntil: dayjs().add(1, 'second').toISOString(),
      }),
    ];

    // ACT
    const result = getEventAchievementTimeStatus(achievement, eventAchievements);

    // ASSERT
    expect(result).toEqual(0);
  });

  it('given an achievement that has expired, returns expired status', () => {
    // ARRANGE
    const activeFrom = dayjs().subtract(2, 'second').toISOString();
    const expiryDate = dayjs().subtract(1, 'second').toISOString();

    const achievement = createAchievement({ id: 1 });
    const evnt = createRaEvent({ activeFrom: null, activeThrough: null, activeUntil: null });
    const eventAchievements: App.Platform.Data.EventAchievement[] = [
      createEventAchievement({
        achievement,
        event: evnt,
        activeFrom,
        activeThrough: expiryDate,
        activeUntil: expiryDate,
      }),
    ];

    // ACT
    const result = getEventAchievementTimeStatus(achievement, eventAchievements);

    // ASSERT
    expect(result).toEqual(1);
  });

  it('given an achievement that is upcoming within 30 days, returns upcoming status', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 1 });
    const evnt = createRaEvent({ activeFrom: null, activeThrough: null, activeUntil: null });
    const eventAchievements = [
      createEventAchievement({
        achievement,
        event: evnt,
        activeFrom: dayjs().add(15, 'day').toISOString(),
        activeThrough: dayjs().add(20, 'day').toISOString(),
      }),
    ];

    // ACT
    const result = getEventAchievementTimeStatus(achievement, eventAchievements);

    // ASSERT
    expect(result).toEqual(2);
  });

  it('given an achievement that is upcoming but more than 30 days away, returns future status', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 1 });
    const evnt = createRaEvent({ activeFrom: null, activeThrough: null, activeUntil: null });
    const eventAchievements = [
      createEventAchievement({
        achievement,
        event: evnt,
        activeFrom: dayjs().add(40, 'day').toISOString(),
        activeThrough: dayjs().add(45, 'day').toISOString(),
      }),
    ];

    // ACT
    const result = getEventAchievementTimeStatus(achievement, eventAchievements);

    // ASSERT
    expect(result).toEqual(3);
  });

  it('given an achievement with activeUntil different from activeThrough, uses activeUntil for active check', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 1 });
    const evnt = createRaEvent({ activeFrom: null, activeThrough: null, activeUntil: null });
    const eventAchievements = [
      createEventAchievement({
        achievement,
        event: evnt,
        activeFrom: dayjs().subtract(1, 'second').toISOString(),
        activeThrough: dayjs().add(1, 'second').toISOString(),
        activeUntil: dayjs().add(2, 'second').add(1, 'day').toISOString(),
      }),
    ];

    // ACT
    const result = getEventAchievementTimeStatus(achievement, eventAchievements);

    // ASSERT
    expect(result).toEqual(0);
  });

  it('given an achievement where now is after activeThrough but before activeUntil, returns active status', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 1 });
    const evnt = createRaEvent({ activeFrom: null, activeThrough: null, activeUntil: null });
    const eventAchievements = [
      createEventAchievement({
        achievement,
        event: evnt,
        activeFrom: dayjs().subtract(2, 'second').toISOString(),
        activeThrough: dayjs().subtract(0.5, 'second').toISOString(), // already passed
        activeUntil: dayjs().add(1, 'second').add(1, 'day').toISOString(), // still active
      }),
    ];

    // ACT
    const result = getEventAchievementTimeStatus(achievement, eventAchievements);

    // ASSERT
    expect(result).toEqual(0);
  });

  it('given an achievement with no start or end date, returns evergreen status', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 1 });
    const evnt = createRaEvent({ activeFrom: null, activeThrough: null, activeUntil: null });
    const eventAchievements = [
      createEventAchievement({
        achievement,
        event: evnt,
      }),
    ];

    // ACT
    const result = getEventAchievementTimeStatus(achievement, eventAchievements);

    // ASSERT
    expect(result).toEqual(4);
  });

  it('given an achievement with no end date and past the start date, returns evergreen status', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 1 });
    const evnt = createRaEvent({ activeFrom: null, activeThrough: null, activeUntil: null });
    const eventAchievements = [
      createEventAchievement({
        achievement,
        event: evnt,
        activeFrom: dayjs().subtract(2, 'second').toISOString(),
      }),
    ];

    // ACT
    const result = getEventAchievementTimeStatus(achievement, eventAchievements);

    // ASSERT
    expect(result).toEqual(4);
  });

  it('given an achievement with no end date but not past the start date, returns upcoming status', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 1 });
    const evnt = createRaEvent({ activeFrom: null, activeThrough: null, activeUntil: null });
    const eventAchievements = [
      createEventAchievement({
        achievement,
        event: evnt,
        activeFrom: dayjs().add(2, 'days').toISOString(),
      }),
    ];

    // ACT
    const result = getEventAchievementTimeStatus(achievement, eventAchievements);

    // ASSERT
    expect(result).toEqual(2);
  });

  it('given an achievement with no start or end dates, but event has active start and end dates, returns active status', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 1 });
    const evnt = createRaEvent({
      activeFrom: dayjs().subtract(1, 'second').toISOString(),
      activeThrough: dayjs().add(1, 'second').toISOString(),
      activeUntil: dayjs().add(2, 'second').add(1, 'day').toISOString(),
    });

    const eventAchievements = [
      createEventAchievement({
        achievement,
        event: evnt,
      }),
    ];

    // ACT
    const result = getEventAchievementTimeStatus(achievement, eventAchievements);

    // ASSERT
    expect(result).toEqual(0);
  });

  it('given an achievement with no start or end dates, but event has end date in the past, returns expired status', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 1 });
    const evnt = createRaEvent({
      activeFrom: dayjs().subtract(8, 'days').toISOString(),
      activeThrough: dayjs().subtract(3, 'days').toISOString(),
      activeUntil: dayjs().subtract(2, 'days').toISOString(),
    });

    const eventAchievements = [
      createEventAchievement({
        achievement,
        event: evnt,
      }),
    ];

    // ACT
    const result = getEventAchievementTimeStatus(achievement, eventAchievements);

    // ASSERT
    expect(result).toEqual(1);
  });
});
