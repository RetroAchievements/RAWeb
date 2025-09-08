import { createLeaderboard } from '@/test/factories';

import { sortLeaderboards } from './sortLeaderboards';

describe('Util: sortLeaderboards', () => {
  it('is defined', () => {
    // ASSERT
    expect(sortLeaderboards).toBeDefined();
  });

  describe('displayOrder sort', () => {
    it('given different orderColumn values, sorts by orderColumn ascending', () => {
      // ARRANGE
      const baseLeaderboard = createLeaderboard();
      const leaderboards = [
        { ...baseLeaderboard, id: 1, orderColumn: 3 },
        { ...baseLeaderboard, id: 2, orderColumn: 1 },
        { ...baseLeaderboard, id: 3, orderColumn: 2 },
      ];

      // ACT
      const result = sortLeaderboards(leaderboards, 'displayOrder');

      // ASSERT
      expect(result.map((l) => l.id)).toEqual([2, 3, 1]);
    });

    it('given same orderColumn, sorts by id ascending', () => {
      // ARRANGE
      const baseLeaderboard = createLeaderboard();
      const leaderboards = [
        { ...baseLeaderboard, id: 3, orderColumn: 1 },
        { ...baseLeaderboard, id: 1, orderColumn: 1 },
        { ...baseLeaderboard, id: 2, orderColumn: 1 },
      ];

      // ACT
      const result = sortLeaderboards(leaderboards, 'displayOrder');

      // ASSERT
      expect(result.map((l) => l.id)).toEqual([1, 2, 3]);
    });

    it('given missing orderColumn values, handles them gracefully', () => {
      // ARRANGE
      const baseLeaderboard = createLeaderboard();
      const leaderboards = [
        { ...baseLeaderboard, id: 1, orderColumn: undefined },
        { ...baseLeaderboard, id: 2, orderColumn: 1 },
        { ...baseLeaderboard, id: 3, orderColumn: 2 },
      ];

      // ACT
      const result = sortLeaderboards(leaderboards, 'displayOrder');

      // ASSERT
      expect(result.map((l) => l.id)).toEqual([1, 2, 3]);
    });
  });

  it('given -displayOrder sort, reverses order', () => {
    // ARRANGE
    const baseLeaderboard = createLeaderboard();
    const leaderboards = [
      { ...baseLeaderboard, id: 1, orderColumn: 1 },
      { ...baseLeaderboard, id: 2, orderColumn: 2 },
      { ...baseLeaderboard, id: 3, orderColumn: 3 },
    ];

    // ACT
    const result = sortLeaderboards(leaderboards, '-displayOrder');

    // ASSERT
    expect(result.map((l) => l.id)).toEqual([3, 2, 1]);
  });

  it('given title sort, sorts alphabetically case-insensitively', () => {
    // ARRANGE
    const baseLeaderboard = createLeaderboard();
    const leaderboards = [
      { ...baseLeaderboard, id: 1, title: 'Charlie' },
      { ...baseLeaderboard, id: 2, title: 'alpha' },
      { ...baseLeaderboard, id: 3, title: 'Beta' },
    ];

    // ACT
    const result = sortLeaderboards(leaderboards, 'title');

    // ASSERT
    expect(result.map((l) => l.id)).toEqual([2, 3, 1]);
  });

  it('given -title sort, sorts alphabetically case-insensitively in reverse', () => {
    // ARRANGE
    const baseLeaderboard = createLeaderboard();
    const leaderboards = [
      { ...baseLeaderboard, id: 1, title: 'Charlie' },
      { ...baseLeaderboard, id: 2, title: 'alpha' },
      { ...baseLeaderboard, id: 3, title: 'Beta' },
    ];

    // ACT
    const result = sortLeaderboards(leaderboards, '-title');

    // ASSERT
    expect(result.map((l) => l.id)).toEqual([1, 3, 2]);
  });

  it('given an unsupported sort order, returns the original order', () => {
    // ARRANGE
    const baseLeaderboard = createLeaderboard();
    const leaderboards = [
      { ...baseLeaderboard, id: 3 },
      { ...baseLeaderboard, id: 1 },
      { ...baseLeaderboard, id: 2 },
    ];

    // ACT
    const result = sortLeaderboards(leaderboards, 'wonBy');

    // ASSERT
    expect(result.map((l) => l.id)).toEqual([3, 1, 2]);
  });

  it('given an empty array, returns an empty array', () => {
    // ARRANGE
    const leaderboards: App.Platform.Data.Leaderboard[] = [];

    // ACT
    const result = sortLeaderboards(leaderboards, 'displayOrder');

    // ASSERT
    expect(result).toEqual([]);
  });

  it('given a single item array, returns the same array', () => {
    // ARRANGE
    const leaderboards = [createLeaderboard({ id: 1 })];

    // ACT
    const result = sortLeaderboards(leaderboards, 'title');

    // ASSERT
    expect(result.map((l) => l.id)).toEqual([1]);
  });
});
