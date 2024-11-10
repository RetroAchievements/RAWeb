import { createGame, createSystem } from '@/test/factories';

import { getIsEventGame } from './getIsEventGame';

describe('Util: getIsEventGame', () => {
  it('is defined', () => {
    // ASSERT
    expect(getIsEventGame).toBeDefined();
  });

  it('given the game has an undefined system, returns false', () => {
    // ARRANGE
    const game = createGame({ system: undefined });

    // ACT
    const result = getIsEventGame(game);

    // ASSERT
    expect(result).toBeFalsy();
  });

  it('given the game has a defined system with an ID that is not 101, returns false', () => {
    // ARRANGE
    const system = createSystem({ id: 1 });
    const game = createGame({ system });

    // ACT
    const result = getIsEventGame(game);

    // ASSERT
    expect(result).toBeFalsy();
  });

  it('given the game has a defined system with an ID that is 101, returns true', () => {
    // ARRANGE
    const system = createSystem({ id: 101 });
    const game = createGame({ system });

    // ACT
    const result = getIsEventGame(game);

    // ASSERT
    expect(result).toBeTruthy();
  });
});
