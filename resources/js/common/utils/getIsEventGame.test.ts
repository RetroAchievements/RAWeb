import { createGame } from '@/test/factories';

import { getIsEventGame } from './getIsEventGame';

describe('Util: getIsEventGame', () => {
  it('is defined', () => {
    // ASSERT
    expect(getIsEventGame).toBeDefined();
  });

  it('given game.system is undefined, returns false', () => {
    // ACT
    const result = getIsEventGame(createGame({ system: undefined }));

    // ASSERT
    expect(result).toEqual(false);
  });
});
