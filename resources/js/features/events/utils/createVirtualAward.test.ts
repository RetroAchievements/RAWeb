import {
  createAchievement,
  createEventAchievement,
  createGame,
  createRaEvent,
} from '@/test/factories';

import { createVirtualAward } from './createVirtualAward';

describe('Util: createVirtualAward', () => {
  it('is defined', () => {
    // ASSERT
    expect(createVirtualAward).toBeDefined();
  });

  it('given the event is missing eventAchievements, returns null', () => {
    // ARRANGE
    const event = createRaEvent({
      id: 1,
      eventAchievements: [], // !!
      legacyGame: createGame({
        badgeUrl: 'https://example.com/badge.jpg',
        title: 'Test Game',
      }),
    });

    // ACT
    const result = createVirtualAward(event, 100);

    // ASSERT
    expect(result).toBeNull();
  });

  it('given the event is missing legacyGame.badgeUrl, returns null', () => {
    // ARRANGE
    const event = createRaEvent({
      id: 1,
      eventAchievements: [],
      legacyGame: createGame({
        title: 'Test Game',
        badgeUrl: undefined, // !!
      }),
    });

    // ACT
    const result = createVirtualAward(event, 0);

    // ASSERT
    expect(result).toBeNull();
  });

  it('given the event is missing legacyGame.title, returns null', () => {
    // ARRANGE
    const event = createRaEvent({
      id: 1,
      eventAchievements: [],
      legacyGame: createGame({
        title: undefined, // !!
        badgeUrl: 'https://example.com/badge.jpg',
      }),
    });

    // ACT
    const result = createVirtualAward(event, 0);

    // ASSERT
    expect(result).toBeNull();
  });

  it('given the event has achievements with points, calculates the total points correctly', () => {
    // ARRANGE
    const event = createRaEvent({
      id: 123,
      eventAchievements: [
        createEventAchievement({ achievement: createAchievement({ points: 5 }) }),
        createEventAchievement({ achievement: createAchievement({ points: 10 }) }),
        createEventAchievement({ achievement: createAchievement({ points: 25 }) }),
      ],
      legacyGame: createGame({
        badgeUrl: 'https://example.com/badge.jpg',
        title: 'Test Game',
      }),
    });

    // ACT
    const result = createVirtualAward(event, 5);

    // ASSERT
    expect(result).toEqual({
      id: 0,
      badgeUrl: 'https://example.com/badge.jpg',
      earnedAt: null,
      eventId: 123,
      label: 'Test Game',
      pointsRequired: 40, // !! 5 + 10 + 25
      tierIndex: 0,
      badgeCount: 5,
    });
  });

  it('given the event has achievements without points, filters them out when calculating total', () => {
    // ARRANGE
    const event = createRaEvent({
      id: 123,
      eventAchievements: [
        createEventAchievement({ achievement: createAchievement({ points: 5 }) }),
        createEventAchievement({ achievement: createAchievement({ points: 0 }) }), // !!
        createEventAchievement({ achievement: createAchievement({ points: 10 }) }),
        createEventAchievement({ achievement: createAchievement({ points: 0 }) }), // !!
        createEventAchievement({ achievement: createAchievement({ points: 25 }) }),
      ],
      legacyGame: createGame({
        badgeUrl: 'https://example.com/badge.jpg',
        title: 'Test Game',
      }),
    });

    // ACT
    const result = createVirtualAward(event, 3);

    // ASSERT
    expect(result).toEqual({
      id: 0,
      badgeUrl: 'https://example.com/badge.jpg',
      earnedAt: null,
      eventId: 123,
      label: 'Test Game',
      pointsRequired: 40, // 5 + 10 + 25
      tierIndex: 0,
      badgeCount: 3,
    });
  });

  it('given the event has no achievements with points, returns null', () => {
    // ARRANGE
    const event = createRaEvent({
      id: 123,
      eventAchievements: [
        createEventAchievement({ achievement: createAchievement({ points: 0 }) }), // !!
      ],
      legacyGame: createGame({
        badgeUrl: 'https://example.com/badge.jpg',
        title: 'Test Game',
      }),
    });

    // ACT
    const result = createVirtualAward(event, 1);

    // ASSERT
    expect(result).toBeNull();
  });

  it('passes numMasters through to the badgeCount property', () => {
    // ARRANGE
    const event = createRaEvent({
      id: 123,
      eventAchievements: [
        createEventAchievement({ achievement: createAchievement({ points: 10 }) }),
      ],
      legacyGame: createGame({
        badgeUrl: 'https://example.com/badge.jpg',
        title: 'Test Game',
      }),
    });

    const numMasters = 42;

    // ACT
    const result = createVirtualAward(event, numMasters);

    // ASSERT
    expect(result!.badgeCount).toEqual(42);
  });

  it('given all achievements are earned, sets earnedAt to the most recent unlock date', () => {
    // ARRANGE
    const event = createRaEvent({
      id: 123,
      eventAchievements: [
        createEventAchievement({
          achievement: createAchievement({
            points: 5,
            unlockedHardcoreAt: '2024-01-01T00:00:00Z',
          }),
        }),
        createEventAchievement({
          achievement: createAchievement({
            points: 10,
            unlockedHardcoreAt: '2024-03-15T00:00:00Z',
          }),
        }),
        createEventAchievement({
          achievement: createAchievement({
            points: 25,
            unlockedHardcoreAt: '2024-02-01T00:00:00Z',
          }),
        }),
      ],
      legacyGame: createGame({
        badgeUrl: 'https://example.com/badge.jpg',
        title: 'Test Game',
      }),
    });

    // ACT
    const result = createVirtualAward(event, 1);

    // ASSERT
    expect(result!.earnedAt).toBe('2024-03-15T00:00:00Z');
  });

  it('given not all achievements are earned, sets earnedAt to null', () => {
    // ARRANGE
    const event = createRaEvent({
      id: 123,
      eventAchievements: [
        createEventAchievement({
          achievement: createAchievement({
            points: 5,
            unlockedHardcoreAt: '2024-01-01T00:00:00Z',
          }),
        }),
        createEventAchievement({
          achievement: createAchievement({
            points: 10,
            unlockedHardcoreAt: undefined, // !!
          }),
        }),
        createEventAchievement({
          achievement: createAchievement({
            points: 25,
            unlockedHardcoreAt: '2024-02-01T00:00:00Z',
          }),
        }),
      ],
      legacyGame: createGame({
        badgeUrl: 'https://example.com/badge.jpg',
        title: 'Test Game',
      }),
    });

    // ACT
    const result = createVirtualAward(event, 1);

    // ASSERT
    expect(result!.earnedAt).toBeNull();
  });
});
