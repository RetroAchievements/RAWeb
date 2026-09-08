import { getGameHashDisplayLabel } from './getGameHashDisplayLabel';

describe('Util: getGameHashDisplayLabel', () => {
  it('given a name with a region tag, returns only the tag', () => {
    // ACT
    const result = getGameHashDisplayLabel({
      md5: 'abcdef0123456789abcdef0123456789',
      name: 'Sonic The Hedgehog (USA, Europe).md',
    });

    // ASSERT
    expect(result).toEqual('(USA, Europe)');
  });

  it('given a name with several tags, joins them with a space', () => {
    // ACT
    const result = getGameHashDisplayLabel({
      md5: 'abcdef0123456789abcdef0123456789',
      name: 'Super Metroid (Japan, USA) (En,Ja) [T+Fre v1.1].sfc',
    });

    // ASSERT
    expect(result).toEqual('(Japan, USA) (En,Ja) [T+Fre v1.1]');
  });

  it('given a name with no tags, returns a short md5 prefix', () => {
    // ACT
    const result = getGameHashDisplayLabel({
      md5: 'abcdef0123456789abcdef0123456789',
      name: 'Some Homebrew Game.nes',
    });

    // ASSERT
    expect(result).toEqual('abcdef01');
  });

  // there's ~125 of these
  it('given there is no name, returns a short md5 prefix', () => {
    // ACT
    const result = getGameHashDisplayLabel({
      md5: 'abcdef0123456789abcdef0123456789',
      name: null,
    });

    // ASSERT
    expect(result).toEqual('abcdef01');
  });
});
