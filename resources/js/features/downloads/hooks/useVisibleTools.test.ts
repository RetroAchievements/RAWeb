import { renderHook } from '@/test';
import { createEmulator, createPlatform, createSystem } from '@/test/factories';

import {
  searchQueryAtom,
  selectedPlatformIdAtom,
  selectedSystemIdAtom,
} from '../state/downloads.atoms';
import { useVisibleTools } from './useVisibleTools';

describe('Hook: useVisibleTools', () => {
  const createTestData = () => {
    const alphaTool = createEmulator({
      id: 1,
      name: 'Alpha Tool',
      hasOfficialSupport: true,
      platforms: [createPlatform({ id: 1, name: 'Windows' })],
      systems: [createSystem({ id: 102, name: 'Standalones' })],
    });

    const betaTool = createEmulator({
      id: 2,
      name: 'Beta Tool',
      hasOfficialSupport: true,
      platforms: [
        createPlatform({ id: 1, name: 'Windows' }),
        createPlatform({ id: 2, name: 'Linux' }),
      ],
      systems: [createSystem({ id: 102, name: 'Standalones' })],
    });

    const gammaTool = createEmulator({
      id: 3,
      name: 'Gamma Tool',
      hasOfficialSupport: true,
      platforms: [createPlatform({ id: 2, name: 'Linux' })],
      systems: [createSystem({ id: 102, name: 'Standalones' })],
    });

    return [alphaTool, betaTool, gammaTool];
  };

  it('renders without crashing', () => {
    // ARRANGE
    const mockTools = createTestData();
    const pageProps = {
      allTools: mockTools,
    };

    const { result } = renderHook(() => useVisibleTools(), {
      pageProps,
    });

    // ASSERT
    expect(result.current).toBeTruthy();
    expect(result.current.visibleTools).toBeTruthy();
  });

  it('given no filters are applied, returns all officially supported tools', () => {
    // ARRANGE
    const mockTools = createTestData();
    const pageProps = {
      allTools: mockTools,
    };

    const { result } = renderHook(() => useVisibleTools(), {
      pageProps,
    });

    // ASSERT
    expect(result.current.visibleTools.length).toEqual(3);
    expect(result.current.visibleTools.map((e) => e.id)).toEqual([1, 2, 3]);
  });

  it('given a platform filter is applied, returns only tools supporting that platform', () => {
    // ARRANGE
    const mockTools = createTestData();
    const pageProps = {
      allTools: mockTools,
    };

    const { result } = renderHook(() => useVisibleTools(), {
      pageProps,
      jotaiAtoms: [
        [selectedPlatformIdAtom, 2], // Linux
        //
      ],
    });

    // ASSERT
    expect(result.current.visibleTools.length).toEqual(2);
    expect(result.current.visibleTools.map((e) => e.id)).toEqual([2, 3]);
  });

  it('given a system filter is applied, returns all tools', () => {
    // ARRANGE
    const mockTools = createTestData();
    const pageProps = {
      allTools: mockTools,
    };

    const { result } = renderHook(() => useVisibleTools(), {
      pageProps,
      jotaiAtoms: [
        [selectedSystemIdAtom, 3], // Genesis
        //
      ],
    });

    // ASSERT
    expect(result.current.visibleTools.length).toEqual(3); // all officially supported tools
  });

  it('given a search query is provided with at least 3 characters, filters by name', () => {
    // ARRANGE
    const mockTools = createTestData();
    const pageProps = {
      allTools: mockTools,
    };

    const { result } = renderHook(() => useVisibleTools(), {
      pageProps,
      jotaiAtoms: [
        [searchQueryAtom, 'Alpha'],
        //
      ],
    });

    // ASSERT
    expect(result.current.visibleTools.length).toEqual(1);
    expect(result.current.visibleTools[0].id).toEqual(1);
  });

  it('given a search query is provided with less than 3 characters, does not filter results', () => {
    // ARRANGE
    const mockTools = createTestData();
    const pageProps = {
      allTools: mockTools,
    };

    const { result } = renderHook(() => useVisibleTools(), {
      pageProps,
      jotaiAtoms: [
        [searchQueryAtom, 'Al'],
        //
      ],
    });

    // ASSERT
    expect(result.current.visibleTools.length).toEqual(3); // all officially supported tools
  });
});
