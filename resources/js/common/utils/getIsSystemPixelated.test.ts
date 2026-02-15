import { getIsSystemPixelated } from './getIsSystemPixelated';

describe('Util: getIsSystemPixelated', () => {
  it.each([
    { systemId: 3, name: 'SNES' },
    { systemId: 4, name: 'Game Boy' },
    { systemId: 7, name: 'NES' },
    { systemId: 27, name: 'Arcade' },
  ])('returns true for $name (id: $systemId)', ({ systemId }) => {
    // ASSERT
    expect(getIsSystemPixelated(systemId)).toEqual(true);
  });

  it.each([
    { systemId: 2, name: 'Nintendo 64' },
    { systemId: 12, name: 'PlayStation' },
    { systemId: 16, name: 'GameCube' },
    { systemId: 18, name: 'Nintendo DS' },
    { systemId: 21, name: 'PlayStation 2' },
    { systemId: 41, name: 'PSP' },
    { systemId: 62, name: 'Nintendo 3DS' },
  ])('returns false for $name (id: $systemId)', ({ systemId }) => {
    // ASSERT
    expect(getIsSystemPixelated(systemId)).toEqual(false);
  });
});
