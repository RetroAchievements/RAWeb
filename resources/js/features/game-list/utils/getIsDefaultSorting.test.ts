import type { SortingState } from '@tanstack/react-table';

import { getIsDefaultSorting } from './getIsDefaultSorting';

describe('Util: getIsDefaultSorting', () => {
  it('returns true when the active sorting matches the default sorting', () => {
    // ARRANGE
    const sorting: SortingState = [{ id: 'title', desc: false }];

    // ACT
    const result = getIsDefaultSorting(sorting, { id: 'title', desc: false });

    // ASSERT
    expect(result).toEqual(true);
  });

  it('returns false when the active sorting does not match the default sorting', () => {
    // ARRANGE
    const sorting: SortingState = [{ id: 'playersTotal', desc: true }];

    // ACT
    const result = getIsDefaultSorting(sorting, { id: 'title', desc: false });

    // ASSERT
    expect(result).toEqual(false);
  });

  it('returns false when no sorting is active', () => {
    // ACT
    const result = getIsDefaultSorting([], { id: 'title', desc: false });

    // ASSERT
    expect(result).toEqual(false);
  });
});
