import type { SortingState } from '@tanstack/react-table';

import { buildGameListQuerySortParam } from './buildGameListQuerySortParam';

/**
 * This test suite is only present to verify handling of an edge case.
 * Ideally, the util is implicitly tested via a component that uses it.
 */

describe('Util: buildGameListQuerySortParam', () => {
  it('is defined', () => {
    // ASSERT
    expect(buildGameListQuerySortParam).toBeDefined();
  });

  it('given there is no sorting state, falls back to null', () => {
    // ARRANGE
    const sorting: SortingState = [];

    // ACT
    const result = buildGameListQuerySortParam(sorting);

    // ASSERT
    expect(result).toBeNull();
  });
});
