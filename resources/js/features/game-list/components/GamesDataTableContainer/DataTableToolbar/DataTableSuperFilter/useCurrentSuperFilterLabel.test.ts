import type { Column, Table } from '@tanstack/react-table';

import { renderHook } from '@/test';

import { useCurrentSuperFilterLabel } from './useCurrentSuperFilterLabel';

function createMockColumn(id: string, filterValue?: any): Column<any, any> {
  return {
    id,
    getFilterValue: () => filterValue,
  } as Column<any, any>;
}

function createMockTable(columns: Record<string, any>): Table<any> {
  const mockColumns = Object.entries(columns).map(([id, filterValue]) =>
    createMockColumn(id, filterValue),
  );

  return {
    getAllColumns: () => mockColumns,
    getColumn: (id: string) => mockColumns.find((col) => col.id === id),
  } as Table<any>;
}

describe('Hook: useCurrentSuperFilterLabel', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const mockTable = createMockTable({});

    const { result } = renderHook(() => useCurrentSuperFilterLabel(mockTable));

    // ASSERT
    expect(result.current).toBeDefined();
  });

  it('given no filters are active, returns "All Games, All Systems"', () => {
    // ARRANGE
    const mockTable = createMockTable({
      achievementsPublished: undefined,
      system: undefined,
    });

    const { result } = renderHook(() => useCurrentSuperFilterLabel(mockTable));

    // ASSERT
    expect(result.current).toEqual('All Games, All Systems');
  });

  it('given achievementsPublished filter is "has", returns "Playable, All Systems"', () => {
    // ARRANGE
    const mockTable = createMockTable({
      achievementsPublished: 'has', // !!
      system: undefined,
    });

    const { result } = renderHook(() => useCurrentSuperFilterLabel(mockTable));

    // ASSERT
    expect(result.current).toEqual('Playable, All Systems');
  });

  it('given achievementsPublished filter is "none", returns "Not Playable, All Systems"', () => {
    // ARRANGE
    const mockTable = createMockTable({
      achievementsPublished: 'none', // !!
      system: undefined,
    });

    const { result } = renderHook(() => useCurrentSuperFilterLabel(mockTable));

    // ASSERT
    expect(result.current).toEqual('Not Playable, All Systems');
  });

  it('given one system is selected, returns "All Games, 1 System"', () => {
    // ARRANGE
    const mockTable = createMockTable({
      achievementsPublished: undefined,
      system: ['1'], // !! single system
    });

    const { result } = renderHook(() => useCurrentSuperFilterLabel(mockTable));

    // ASSERT
    expect(result.current).toEqual('All Games, 1 System');
  });

  it('given multiple systems are selected, returns "All Games, 3 Systems"', () => {
    // ARRANGE
    const mockTable = createMockTable({
      achievementsPublished: undefined,
      system: ['1', '2', '3'], // !! multiple systems
    });

    const { result } = renderHook(() => useCurrentSuperFilterLabel(mockTable));

    // ASSERT
    expect(result.current).toEqual('All Games, 3 Systems');
  });

  it('given both achievementsPublished and system filters are active, combines them', () => {
    // ARRANGE
    const mockTable = createMockTable({
      achievementsPublished: 'has',
      system: ['1', '2'],
    });

    const { result } = renderHook(() => useCurrentSuperFilterLabel(mockTable));

    // ASSERT
    expect(result.current).toEqual('Playable, 2 Systems');
  });

  it('given an unknown achievementsPublished filter value, falls back to "All Games"', () => {
    // ARRANGE
    const mockTable = createMockTable({
      achievementsPublished: 'unknown-value', // !!
      system: undefined,
    });

    const { result } = renderHook(() => useCurrentSuperFilterLabel(mockTable));

    // ASSERT
    expect(result.current).toEqual('All Games, All Systems');
  });

  it('given the achievementsPublished column does not exist, returns "All Games"', () => {
    // ARRANGE
    const mockTable = createMockTable({}); // !! no achievementsPublished column

    const { result } = renderHook(() => useCurrentSuperFilterLabel(mockTable));

    // ASSERT
    expect(result.current).toEqual('All Games');
  });

  it('given the system column does not exist, omits system information', () => {
    // ARRANGE
    const mockTable = createMockTable({
      achievementsPublished: 'has',
      // !! no system column
    });

    const { result } = renderHook(() => useCurrentSuperFilterLabel(mockTable));

    // ASSERT
    expect(result.current).toEqual('Playable');
  });

  it('given we are on the set-request page with "supported" system filter, returns "All Games, Supported"', () => {
    // ARRANGE
    const mockTable = createMockTable({
      achievementsPublished: undefined,
      system: ['supported'], // !!
    });

    const { result } = renderHook(
      () => useCurrentSuperFilterLabel(mockTable, 'api.set-request.index'), // !!
    );

    // ASSERT
    expect(result.current).toEqual('All Games, Supported');
  });

  it('given we are not on the set-request page with "supported" system filter, treats it as normal system', () => {
    // ARRANGE
    const mockTable = createMockTable({
      achievementsPublished: undefined,
      system: ['supported'],
    });

    const { result } = renderHook(
      () => useCurrentSuperFilterLabel(mockTable, 'api.game.index'), // !!
    );

    // ASSERT
    expect(result.current).toEqual('All Games, 1 System');
  });

  it('given an empty system filter array, returns "All Systems"', () => {
    // ARRANGE
    const mockTable = createMockTable({
      achievementsPublished: 'has',
      system: [], // !!
    });

    const { result } = renderHook(() => useCurrentSuperFilterLabel(mockTable));

    // ASSERT
    expect(result.current).toEqual('Playable, All Systems');
  });
});
