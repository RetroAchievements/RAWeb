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

  it('given a name with several tags, retains them', () => {
    // ACT
    const result = getGameHashDisplayLabel({
      md5: 'abcdef0123456789abcdef0123456789',
      name: 'Super Metroid (Japan, USA) (En,Ja) [T+Fre v1.1].sfc',
    });

    // ASSERT
    expect(result).toEqual('(Japan, USA) (En,Ja) [T+Fre v1.1]');
  });

  it('given a file extension followed by a tag, drops the extension and retains the tag', () => {
    // ACT
    const result = getGameHashDisplayLabel({
      md5: 'abcdef0123456789abcdef0123456789',
      name: 'Bleach - Heat the Soul 5 (Japan).iso [legacy]',
    });

    // ASSERT
    expect(result).toEqual('(Japan) [legacy]');
  });

  it('given a title encased in angle brackets, returns only the tags from the title', () => {
    // ACT
    const result = getGameHashDisplayLabel({
      md5: 'abcdef0123456789abcdef0123456789',
      name: 'avsp.7z <Alien vs Predator (940520 Euro)>',
    });

    // ASSERT
    expect(result).toEqual('(940520 Euro)');
  });

  it('given some loose text after a tag, retains it and still removes the extension', () => {
    // ACT
    const result = getGameHashDisplayLabel({
      md5: 'abcdef0123456789abcdef0123456789',
      name: 'The Black Cauldron (4am and san inc crack) disk 1A.dsk',
    });

    // ASSERT
    expect(result).toEqual('(4am and san inc crack) disk 1A');
  });

  it('given a hack name between tags, retains the hack name', () => {
    // ACT
    const result = getGameHashDisplayLabel({
      md5: 'abcdef0123456789abcdef0123456789',
      name: 'Metroid (USA) - Junkoid (1.1) (P. Yoshi).nes',
    });

    // ASSERT
    expect(result).toEqual('(USA) - Junkoid (1.1) (P. Yoshi)');
  });

  it('given a tag before the title, retains the tag and still removes the title', () => {
    // ACT
    const result = getGameHashDisplayLabel({
      md5: 'abcdef0123456789abcdef0123456789',
      name: '[MSU-1] Chrono Trigger (USA) (DarkShock) (v1.1).sfc',
    });

    // ASSERT
    expect(result).toEqual('[MSU-1] (USA) (DarkShock) (v1.1)');
  });

  it('given a trailing version number, does not treat it as a file extension', () => {
    // ACT
    const result = getGameHashDisplayLabel({
      md5: 'abcdef0123456789abcdef0123456789',
      name: 'Mega Man X (USA) v1.1',
    });

    // ASSERT
    expect(result).toEqual('(USA) v1.1');
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
