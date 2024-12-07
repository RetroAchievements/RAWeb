import type { ColumnFiltersState } from '@tanstack/react-table';

import { getAreNonDefaultFiltersSet } from './getAreNonDefaultFiltersSet';

describe('Util: getAreNonDefaultFiltersSet', () => {
  it('is defined', () => {
    // ASSERT
    expect(getAreNonDefaultFiltersSet).toBeDefined();
  });

  it('given different filter lengths, returns true', () => {
    // ARRANGE
    const currentFilters: ColumnFiltersState = [{ id: 'title', value: 'test' }];
    const defaultFilters: ColumnFiltersState = [];

    // ACT
    const result = getAreNonDefaultFiltersSet(currentFilters, defaultFilters);

    // ASSERT
    expect(result).toEqual(true);
  });

  it('given different filter ids, returns true', () => {
    // ARRANGE
    const currentFilters: ColumnFiltersState = [{ id: 'name', value: 'test' }];
    const defaultFilters: ColumnFiltersState = [{ id: 'age', value: 'test' }];

    // ACT
    const result = getAreNonDefaultFiltersSet(currentFilters, defaultFilters);

    // ASSERT
    expect(result).toEqual(true);
  });

  it('given different array values, returns true', () => {
    // ARRANGE
    const currentFilters: ColumnFiltersState = [{ id: 'tags', value: ['tag1', 'tag2'] }];
    const defaultFilters: ColumnFiltersState = [{ id: 'tags', value: ['tag1'] }];

    // ACT
    const result = getAreNonDefaultFiltersSet(currentFilters, defaultFilters);

    // ASSERT
    expect(result).toEqual(true);
  });

  it('given different non-array values, returns true', () => {
    // ARRANGE
    const currentFilters: ColumnFiltersState = [{ id: 'name', value: 'test1' }];
    const defaultFilters: ColumnFiltersState = [{ id: 'name', value: 'test2' }];

    // ACT
    const result = getAreNonDefaultFiltersSet(currentFilters, defaultFilters);

    // ASSERT
    expect(result).toEqual(true);
  });

  it('given identical filters, returns false', () => {
    // ARRANGE
    const currentFilters: ColumnFiltersState = [{ id: 'name', value: 'test' }];
    const defaultFilters: ColumnFiltersState = [{ id: 'name', value: 'test' }];

    // ACT
    const result = getAreNonDefaultFiltersSet(currentFilters, defaultFilters);

    // ASSERT
    expect(result).toEqual(false);
  });

  it('given identical array values, returns false', () => {
    // ARRANGE
    const currentFilters: ColumnFiltersState = [{ id: 'tags', value: ['tag1', 'tag2'] }];
    const defaultFilters: ColumnFiltersState = [{ id: 'tags', value: ['tag1', 'tag2'] }];

    // ACT
    const result = getAreNonDefaultFiltersSet(currentFilters, defaultFilters);

    // ASSERT
    expect(result).toEqual(false);
  });
});
