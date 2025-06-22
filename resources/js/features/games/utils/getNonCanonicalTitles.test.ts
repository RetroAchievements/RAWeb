import { createGameRelease } from '@/test/factories';

import { getNonCanonicalTitles } from './getNonCanonicalTitles';

describe('Util: getNonCanonicalTitles', () => {
  it('is defined', () => {
    // ASSERT
    expect(getNonCanonicalTitles).toBeDefined();
  });

  it('given undefined releases, returns an empty array', () => {
    // ARRANGE
    const releases = undefined;

    // ACT
    const result = getNonCanonicalTitles(releases);

    // ASSERT
    expect(result).toEqual([]);
  });

  it('given an empty array of releases, returns an empty array', () => {
    // ARRANGE
    const releases: App.Platform.Data.GameRelease[] = [];

    // ACT
    const result = getNonCanonicalTitles(releases);

    // ASSERT
    expect(result).toEqual([]);
  });

  it('given releases with a canonical title, excludes the canonical title from results', () => {
    // ARRANGE
    const releases = [
      createGameRelease({
        title: 'Super Mario Bros.',
        isCanonicalGameTitle: true,
        releasedAt: '2023-01-01',
      }),
      createGameRelease({
        title: 'Super Mario Brothers',
        isCanonicalGameTitle: false,
        releasedAt: '2023-01-01',
      }),
      createGameRelease({
        title: 'SMB',
        isCanonicalGameTitle: false,
        releasedAt: '2023-01-01',
      }),
    ];

    // ACT
    const result = getNonCanonicalTitles(releases);

    // ASSERT
    expect(result).toEqual(['SMB', 'Super Mario Brothers']);
  });

  it('given duplicate non-canonical titles, returns unique titles only', () => {
    // ARRANGE
    const releases = [
      createGameRelease({
        title: 'Canonical Game',
        isCanonicalGameTitle: true,
        releasedAt: '2023-01-01',
      }),
      createGameRelease({
        title: 'Alternate Title',
        isCanonicalGameTitle: false,
        releasedAt: '2023-01-01',
        region: 'na',
      }),
      createGameRelease({
        title: 'Alternate Title',
        isCanonicalGameTitle: false,
        releasedAt: '2023-01-01',
        region: 'eu',
      }),
      createGameRelease({
        title: 'Another Title',
        isCanonicalGameTitle: false,
        releasedAt: '2023-01-01',
      }),
    ];

    // ACT
    const result = getNonCanonicalTitles(releases);

    // ASSERT
    expect(result).toEqual(['Alternate Title', 'Another Title']);
  });

  it('given a non-canonical title that matches the canonical title, excludes it', () => {
    // ARRANGE
    const releases = [
      createGameRelease({
        title: 'Final Fantasy VII',
        isCanonicalGameTitle: true,
        releasedAt: '2023-01-01',
      }),
      createGameRelease({
        title: 'Final Fantasy VII',
        isCanonicalGameTitle: false,
        releasedAt: '2023-01-01',
        region: 'jp',
      }),
      createGameRelease({
        title: 'FF7',
        isCanonicalGameTitle: false,
        releasedAt: '2023-01-01',
      }),
    ];

    // ACT
    const result = getNonCanonicalTitles(releases);

    // ASSERT
    expect(result).toEqual(['FF7']);
  });

  it('given releases with tag prefixes, strips tags and deduplicates properly', () => {
    // ARRANGE
    const releases = [
      createGameRelease({
        title: '~Unlicensed~ Digimon Ruby',
        isCanonicalGameTitle: true, // !! canonical has tag prefix
        releasedAt: '2023-01-01',
      }),
      createGameRelease({
        title: 'Digimon Ruby',
        isCanonicalGameTitle: false, // !! same title without tag
        releasedAt: '2023-01-01',
      }),
      createGameRelease({
        title: '~Prototype~ Digimon Ruby',
        isCanonicalGameTitle: false, // !! different tag, same base title
        releasedAt: '2023-01-01',
      }),
      createGameRelease({
        title: 'Digimon Sapphire',
        isCanonicalGameTitle: false, // !! different title
        releasedAt: '2023-01-01',
      }),
    ];

    // ACT
    const result = getNonCanonicalTitles(releases);

    // ASSERT
    expect(result).toEqual(['Digimon Sapphire']); // !! only unique non-canonical title after stripping tags
  });
});
