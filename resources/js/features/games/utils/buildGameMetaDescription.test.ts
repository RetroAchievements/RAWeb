import { createGame, createSystem } from '@/test/factories';

import { buildGameMetaDescription } from './buildGameMetaDescription';

describe('Util: buildGameMetaDescription', () => {
  it('is defined', () => {
    // ASSERT
    expect(buildGameMetaDescription).toBeDefined();
  });

  it('given the game has no achievements published, outputs the correct message', () => {
    // ARRANGE
    const game = createGame({ achievementsPublished: 0 });

    // ACT
    const result = buildGameMetaDescription(game);

    // ASSERT
    expect(result).toContain('No achievements have been created yet');
  });

  it('given the game has achievements published, outputs the correct message', () => {
    // ARRANGE
    const system = createSystem();
    const game = createGame({ system, achievementsPublished: 100, pointsTotal: 800 });

    // ACT
    const result = buildGameMetaDescription(game);

    // ASSERT
    expect(result).toContain('There are 100 achievements');
  });
});
