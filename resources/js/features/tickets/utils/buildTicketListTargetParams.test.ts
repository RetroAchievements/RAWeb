import { createAchievement, createGame } from '@/test/factories';

import { buildTicketListTargetParams } from './buildTicketListTargetParams';

describe('Util: buildTicketListTargetParams', () => {
  it('is defined', () => {
    // ASSERT
    expect(buildTicketListTargetParams).toBeDefined();
  });

  it('given no target, returns no params', () => {
    // ACT
    const result = buildTicketListTargetParams({ achievement: null, game: null });

    // ASSERT
    expect(result).toEqual({});
  });

  it('given a game target, contains the game', () => {
    // ACT
    const result = buildTicketListTargetParams({
      achievement: null,
      game: createGame({ id: 1701 }),
    });

    // ASSERT
    expect(result).toEqual({ game: 1701 });
  });

  it('given an achievement target, contains the achievement', () => {
    // ACT
    const result = buildTicketListTargetParams({
      achievement: createAchievement({ id: 903 }),
      game: null,
    });

    // ASSERT
    expect(result).toEqual({ achievement: 903 });
  });
});
