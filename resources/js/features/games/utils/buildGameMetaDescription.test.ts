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
    const system = createSystem({ name: 'NES' });
    const game = createGame({
      title: 'Super Mario Bros.',
      system,
      achievementsPublished: 100,
      pointsTotal: 800,
    });

    // ACT
    const result = buildGameMetaDescription(game);

    // ASSERT
    expect(result).toBe(
      'There are 100 achievements worth 800 points. Super Mario Bros. for NES - explore and compete on this classic game at RetroAchievements.',
    );
  });

  it('given the game has 1,200 points, formats the number with a comma', () => {
    // ARRANGE
    const system = createSystem({ name: 'SNES' });
    const game = createGame({
      title: 'The Legend of Zelda',
      system,
      achievementsPublished: 50,
      pointsTotal: 1200, // !! should have comma formatting
    });

    // ACT
    const result = buildGameMetaDescription(game);

    // ASSERT
    expect(result).toBe(
      'There are 50 achievements worth 1,200 points. The Legend of Zelda for SNES - explore and compete on this classic game at RetroAchievements.',
    );
  });
});
