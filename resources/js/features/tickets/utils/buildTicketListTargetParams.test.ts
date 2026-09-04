import { createAchievement, createGame, createUser } from '@/test/factories';

import { buildTicketListTargetParams } from './buildTicketListTargetParams';

describe('Util: buildTicketListTargetParams', () => {
  it('is defined', () => {
    // ASSERT
    expect(buildTicketListTargetParams).toBeDefined();
  });

  it('given no target, returns no params', () => {
    // ACT
    const result = buildTicketListTargetParams({ achievement: null, game: null, user: null });

    // ASSERT
    expect(result).toEqual({});
  });

  it('given a game target, contains the game', () => {
    // ACT
    const result = buildTicketListTargetParams({
      achievement: null,
      game: createGame({ id: 1701 }),
      user: null,
    });

    // ASSERT
    expect(result).toEqual({ game: 1701 });
  });

  it('given an achievement target, contains the achievement', () => {
    // ACT
    const result = buildTicketListTargetParams({
      achievement: createAchievement({ id: 903 }),
      game: null,
      user: null,
    });

    // ASSERT
    expect(result).toEqual({ achievement: 903 });
  });

  it('given a user target, contains the user', () => {
    // ACT
    const result = buildTicketListTargetParams({
      achievement: null,
      game: null,
      user: createUser({ id: 5309 }),
    });

    // ASSERT
    expect(result).toEqual({ user: 5309 });
  });

  it('given a user target without an id, does not crash', () => {
    // ACT
    const result = buildTicketListTargetParams({
      achievement: null,
      game: null,
      user: createUser({ id: undefined }),
    });

    // ASSERT
    expect(result).toEqual({});
  });
});
