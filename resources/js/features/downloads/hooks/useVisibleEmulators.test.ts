import { renderHook } from '@/test';
import { createEmulator, createPlatform, createSystem } from '@/test/factories';

import {
  searchQueryAtom,
  selectedPlatformIdAtom,
  selectedSystemIdAtom,
  sortByAtom,
} from '../state/downloads.atoms';
import { useVisibleEmulators } from './useVisibleEmulators';

describe('Hook: useVisibleEmulators', () => {
  const createTestData = () => {
    const alphaEmulator = createEmulator({
      id: 1,
      name: 'Alpha Emulator',
      originalName: 'Alpha',
      hasOfficialSupport: true,
      platforms: [
        createPlatform({ id: 1, name: 'Windows' }),
        createPlatform({ id: 2, name: 'MacOS' }),
      ],
      systems: [createSystem({ id: 1, name: 'NES' }), createSystem({ id: 2, name: 'SNES' })],
    });

    const betaEmulator = createEmulator({
      id: 2,
      name: 'Beta Emulator',
      originalName: 'Beta',
      hasOfficialSupport: true,
      platforms: [createPlatform({ id: 2, name: 'MacOS' })],
      systems: [createSystem({ id: 1, name: 'NES' })],
    });

    const charlieEmulator = createEmulator({
      id: 3,
      name: 'Charlie Emulator',
      originalName: 'Charlie',
      hasOfficialSupport: true,
      platforms: [createPlatform({ id: 1, name: 'Windows' })],
      systems: [createSystem({ id: 3, name: 'Genesis' })],
    });

    const deltaEmulator = createEmulator({
      id: 4,
      name: 'Delta Emulator',
      originalName: 'Delta',
      hasOfficialSupport: false, // !!
      platforms: [
        createPlatform({ id: 1, name: 'Windows' }),
        createPlatform({ id: 2, name: 'MacOS' }),
      ],
      systems: [createSystem({ id: 1, name: 'NES' }), createSystem({ id: 2, name: 'SNES' })],
    });

    const mockEmulators = [alphaEmulator, betaEmulator, charlieEmulator, deltaEmulator];

    const mockPopularEmulatorsBySystem = {
      0: [2, 1, 3], // Overall popularity: Beta, Alpha, Charlie
      1: [2, 1], // NES popularity: Beta, Alpha
      2: [1], // SNES popularity: Alpha
      3: [3], // Genesis popularity: Charlie
    };

    return { mockEmulators, mockPopularEmulatorsBySystem };
  };

  it('renders without crashing', () => {
    // ARRANGE
    const { mockEmulators, mockPopularEmulatorsBySystem } = createTestData();
    const pageProps = {
      allEmulators: mockEmulators,
      popularEmulatorsBySystem: mockPopularEmulatorsBySystem,
    };

    const { result } = renderHook(() => useVisibleEmulators(), {
      pageProps,
    });

    // ASSERT
    expect(result.current).toBeTruthy();
    expect(result.current.visibleEmulators).toBeTruthy();
  });

  it('given no filters are applied, returns all officially supported emulators', () => {
    // ARRANGE
    const { mockEmulators, mockPopularEmulatorsBySystem } = createTestData();
    const pageProps = {
      allEmulators: mockEmulators,
      popularEmulatorsBySystem: mockPopularEmulatorsBySystem,
    };

    const { result } = renderHook(() => useVisibleEmulators(), {
      pageProps,
    });

    // ASSERT
    expect(result.current.visibleEmulators.length).toEqual(3);
    expect(result.current.visibleEmulators.map((e) => e.id)).toEqual([2, 1, 3]);
  });

  it('given a platform filter is applied, returns only emulators supporting that platform', () => {
    // ARRANGE
    const { mockEmulators, mockPopularEmulatorsBySystem } = createTestData();
    const pageProps = {
      allEmulators: mockEmulators,
      popularEmulatorsBySystem: mockPopularEmulatorsBySystem,
    };

    const { result } = renderHook(() => useVisibleEmulators(), {
      pageProps,
      jotaiAtoms: [
        [selectedPlatformIdAtom, 2], // MacOS
        //
      ],
    });

    // ASSERT
    expect(result.current.visibleEmulators.length).toEqual(2);
    expect(result.current.visibleEmulators.map((e) => e.id)).toEqual([2, 1]);
  });

  it('given a system filter is applied, returns only emulators supporting that system', () => {
    // ARRANGE
    const { mockEmulators, mockPopularEmulatorsBySystem } = createTestData();
    const pageProps = {
      allEmulators: mockEmulators,
      popularEmulatorsBySystem: mockPopularEmulatorsBySystem,
    };

    const { result } = renderHook(() => useVisibleEmulators(), {
      pageProps,
      jotaiAtoms: [
        [selectedSystemIdAtom, 3], // Genesis
        //
      ],
    });

    // ASSERT
    expect(result.current.visibleEmulators.length).toEqual(1);
    expect(result.current.visibleEmulators[0].id).toEqual(3);
  });

  it('given both platform and system filters are applied, returns emulators matching both criteria', () => {
    // ARRANGE
    const { mockEmulators, mockPopularEmulatorsBySystem } = createTestData();
    const pageProps = {
      allEmulators: mockEmulators,
      popularEmulatorsBySystem: mockPopularEmulatorsBySystem,
    };

    const { result } = renderHook(() => useVisibleEmulators(), {
      pageProps,
      jotaiAtoms: [
        [selectedPlatformIdAtom, 1], // Windows
        [selectedSystemIdAtom, 1], // NES
        //
      ],
    });

    // ASSERT
    expect(result.current.visibleEmulators.length).toEqual(1);
    expect(result.current.visibleEmulators[0].id).toEqual(1);
  });

  it('given a search query is provided with at least 3 characters, filters by name', () => {
    // ARRANGE
    const { mockEmulators, mockPopularEmulatorsBySystem } = createTestData();
    const pageProps = {
      allEmulators: mockEmulators,
      popularEmulatorsBySystem: mockPopularEmulatorsBySystem,
    };

    const { result } = renderHook(() => useVisibleEmulators(), {
      pageProps,
      jotaiAtoms: [
        [searchQueryAtom, 'Alpha'],
        //
      ],
    });

    // ASSERT
    expect(result.current.visibleEmulators.length).toEqual(1);
    expect(result.current.visibleEmulators[0].id).toEqual(1);
  });

  it('given a search query is provided with less than 3 characters, does not filter results', () => {
    // ARRANGE
    const { mockEmulators, mockPopularEmulatorsBySystem } = createTestData();
    const pageProps = {
      allEmulators: mockEmulators,
      popularEmulatorsBySystem: mockPopularEmulatorsBySystem,
    };

    const { result } = renderHook(() => useVisibleEmulators(), {
      pageProps,
      jotaiAtoms: [
        [searchQueryAtom, 'Al'],
        //
      ],
    });

    // ASSERT
    expect(result.current.visibleEmulators.length).toEqual(3); // all officially supported emulators
  });

  it('given a search query matches originalName, returns matching emulators', () => {
    // ARRANGE
    const { mockEmulators, mockPopularEmulatorsBySystem } = createTestData();
    const pageProps = {
      allEmulators: mockEmulators,
      popularEmulatorsBySystem: mockPopularEmulatorsBySystem,
    };

    const { result } = renderHook(() => useVisibleEmulators(), {
      pageProps,
      jotaiAtoms: [
        [searchQueryAtom, 'Charlie'],
        //
      ],
    });

    // ASSERT
    expect(result.current.visibleEmulators.length).toEqual(1);
    expect(result.current.visibleEmulators[0].id).toEqual(3);
  });

  it("given sortBy is set to 'alphabetical', sorts emulators by name", () => {
    // ARRANGE
    const { mockEmulators, mockPopularEmulatorsBySystem } = createTestData();
    const pageProps = {
      allEmulators: mockEmulators,
      popularEmulatorsBySystem: mockPopularEmulatorsBySystem,
    };

    const { result } = renderHook(() => useVisibleEmulators(), {
      pageProps,
      jotaiAtoms: [
        [sortByAtom, 'alphabetical'],
        //
      ],
    });

    // ASSERT
    expect(result.current.visibleEmulators.map((e) => e.id)).toEqual([1, 2, 3]);
  });

  it("given sortBy is set to 'popularity', sorts emulators by overall popularity when no system is selected", () => {
    // ARRANGE
    const { mockEmulators, mockPopularEmulatorsBySystem } = createTestData();
    const pageProps = {
      allEmulators: mockEmulators,
      popularEmulatorsBySystem: mockPopularEmulatorsBySystem,
    };

    const { result } = renderHook(() => useVisibleEmulators(), {
      pageProps,
      jotaiAtoms: [
        [sortByAtom, 'popularity'],
        //
      ],
    });

    // ASSERT
    expect(result.current.visibleEmulators.map((e) => e.id)).toEqual([2, 1, 3]);
  });

  it("given sortBy is 'popularity' and a system is selected, sorts emulators by that system's popularity", () => {
    // ARRANGE
    const { mockEmulators, mockPopularEmulatorsBySystem } = createTestData();
    const pageProps = {
      allEmulators: mockEmulators,
      popularEmulatorsBySystem: mockPopularEmulatorsBySystem,
    };

    const { result } = renderHook(() => useVisibleEmulators(), {
      pageProps,
      jotaiAtoms: [
        [sortByAtom, 'popularity'],
        [selectedSystemIdAtom, 1], // NES
        //
      ],
    });

    // ASSERT
    expect(result.current.visibleEmulators.map((e) => e.id)).toEqual([2, 1]);
  });

  it('given an emulator is not in the popularity list, places it after listed emulators', () => {
    // ARRANGE
    const { mockEmulators, mockPopularEmulatorsBySystem } = createTestData();

    const echoEmulator = createEmulator({
      id: 5,
      name: 'Echo Emulator',
      originalName: 'Echo',
      hasOfficialSupport: true,
      platforms: [createPlatform({ id: 1, name: 'Windows' })],
      systems: [createSystem({ id: 1, name: 'NES' })],
    });

    const customEmulators = [...mockEmulators, echoEmulator];

    const { result } = renderHook(() => useVisibleEmulators(), {
      pageProps: {
        allEmulators: customEmulators,
        popularEmulatorsBySystem: mockPopularEmulatorsBySystem,
      },
      jotaiAtoms: [
        [sortByAtom, 'popularity'],
        [selectedSystemIdAtom, 1], // NES
        //
      ],
    });

    // ASSERT
    // ... Echo should come after the known popular emulators ...
    expect(result.current.visibleEmulators.map((e) => e.id)).toEqual([2, 1, 5]);
  });

  it('given no emulators are in the popularity list, falls back to alphabetical sorting', () => {
    // ARRANGE
    const { mockEmulators } = createTestData();

    const emptyPopularityLists = {
      0: [],
      1: [],
      2: [],
      3: [],
    };

    const { result } = renderHook(() => useVisibleEmulators(), {
      pageProps: {
        allEmulators: mockEmulators,
        popularEmulatorsBySystem: emptyPopularityLists,
      },
      jotaiAtoms: [
        [sortByAtom, 'popularity'],
        //
      ],
    });

    // ASSERT
    expect(result.current.visibleEmulators.map((e) => e.id)).toEqual([1, 2, 3]);
  });

  it('given only one emulator is in the popularity list, places unlisted emulators after it', () => {
    // ARRANGE
    const { mockEmulators } = createTestData();

    const limitedPopularityLists = {
      0: [2],
      1: [2],
      2: [],
      3: [],
    };

    const { result } = renderHook(() => useVisibleEmulators(), {
      pageProps: {
        allEmulators: mockEmulators,
        popularEmulatorsBySystem: limitedPopularityLists,
      },
      jotaiAtoms: [
        [sortByAtom, 'popularity'],
        //
      ],
    });

    // ASSERT
    expect(result.current.visibleEmulators.map((e) => e.id)).toEqual([2, 1, 3]);
  });

  it('given an invalid sortBy value, falls back to alphabetical sorting', () => {
    // ARRANGE
    const { mockEmulators, mockPopularEmulatorsBySystem } = createTestData();
    const pageProps = {
      allEmulators: mockEmulators,
      popularEmulatorsBySystem: mockPopularEmulatorsBySystem,
    };

    const { result } = renderHook(() => useVisibleEmulators(), {
      pageProps,
      jotaiAtoms: [
        [sortByAtom, 'invalid_sort_value' as any], // !!
        //
      ],
    });

    // ASSERT
    expect(result.current.visibleEmulators.map((e) => e.id)).toEqual([1, 2, 3]);
  });

  it('given there is no popularity for a given system id, does not crash', () => {
    // ARRANGE
    const { mockEmulators } = createTestData();
    const pageProps = {
      allEmulators: mockEmulators,
      popularEmulatorsBySystem: [],
    };

    const { result } = renderHook(() => useVisibleEmulators(), {
      pageProps,
      jotaiAtoms: [
        [sortByAtom, 'popularity'], // !!
        //
      ],
    });

    // ASSERT
    expect(result.current.visibleEmulators.map((e) => e.id)).toEqual([1, 2, 3]);
  });
});
