import { buildGameRarityLabel } from './buildGameRarityLabel';

describe('Util: buildGameRarityLabel', () => {
  it('is defined', () => {
    // ASSERT
    expect(buildGameRarityLabel).toBeDefined();
  });

  it('given the game has no points, returns null', () => {
    // ACT
    const resultOne = buildGameRarityLabel(0, 0);
    const resultTwo = buildGameRarityLabel(undefined, 0);

    // ASSERT
    expect(resultOne).toBeNull();
    expect(resultTwo).toBeNull();
  });

  it('returns a formatted rarity label', () => {
    // ACT
    const result = buildGameRarityLabel(100, 400);

    // ASSERT
    expect(result).toEqual('×4.00');
  });

  it('given there are no weighted points, sets rarity to zero', () => {
    // ACT
    const result = buildGameRarityLabel(100, 0);

    // ASSERT
    expect(result).toEqual('×0.00');
  });
});
