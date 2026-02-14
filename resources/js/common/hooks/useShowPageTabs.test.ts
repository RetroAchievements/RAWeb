import { router } from '@inertiajs/react';
import { atom } from 'jotai';

import { act, renderHook } from '@/test';

import { useShowPageTabs } from './useShowPageTabs';

// Use a standalone atom so tests are fully isolated from any feature module.
const testTabAtom = atom<'first' | 'second' | 'third'>('first');

describe('Hook: useShowPageTabs', () => {
  let originalLocation: Location;

  beforeEach(() => {
    vi.spyOn(router, 'replace').mockImplementation(() => {});

    originalLocation = window.location;
    delete (window as any).location;

    (window.location as any) = {
      ...originalLocation,
      href: 'https://retroachievements.org/page/1',
      pathname: '/page/1',
      search: '',
    } as Location;
  });

  afterEach(() => {
    (window.location as any) = originalLocation;

    vi.restoreAllMocks();
  });

  it('returns the correct function definitions', () => {
    // ACT
    const { result } = renderHook(() => useShowPageTabs(testTabAtom, 'first'), {
      jotaiAtoms: [
        [testTabAtom, 'first'],
        //
      ],
    });

    // ASSERT
    expect(result.current.currentTab).toEqual('first');
    expect(typeof result.current.setCurrentTab).toEqual('function');
  });

  it('given the user sets a non-default tab, updates the atom and adds the tab param to the URL', () => {
    // ARRANGE
    const { result } = renderHook(() => useShowPageTabs(testTabAtom, 'first'), {
      jotaiAtoms: [
        [testTabAtom, 'first'],
        //
      ],
    });

    // ACT
    act(() => {
      result.current.setCurrentTab('second');
    });

    // ASSERT
    expect(result.current.currentTab).toEqual('second');
    expect(router.replace).toHaveBeenCalledWith({
      url: 'https://retroachievements.org/page/1?tab=second',
      preserveScroll: true,
      preserveState: true,
    });
  });

  it('given the user sets another non-default tab, updates the atom and adds the tab param to the URL', () => {
    // ARRANGE
    const { result } = renderHook(() => useShowPageTabs(testTabAtom, 'first'), {
      jotaiAtoms: [
        [testTabAtom, 'first'],
        //
      ],
    });

    // ACT
    act(() => {
      result.current.setCurrentTab('third');
    });

    // ASSERT
    expect(result.current.currentTab).toEqual('third');
    expect(router.replace).toHaveBeenCalledWith({
      url: 'https://retroachievements.org/page/1?tab=third',
      preserveScroll: true,
      preserveState: true,
    });
  });

  it('given the user sets the tab to the default, updates the atom and removes the tab param from the URL', () => {
    // ARRANGE
    (window.location as any).href = 'https://retroachievements.org/page/1?tab=second';
    window.location.search = '?tab=second';
    const { result } = renderHook(() => useShowPageTabs(testTabAtom, 'first'), {
      jotaiAtoms: [
        [testTabAtom, 'second'],
        //
      ],
    });

    // ACT
    act(() => {
      result.current.setCurrentTab('first');
    });

    // ASSERT
    expect(result.current.currentTab).toEqual('first');
    expect(router.replace).toHaveBeenCalledWith({
      url: 'https://retroachievements.org/page/1',
      preserveScroll: true,
      preserveState: true,
    });
  });

  it('given there are existing query params and the user sets a non-default tab, preserves those existing params', () => {
    // ARRANGE
    (window.location as any).href = 'https://retroachievements.org/page/1?foo=bar&baz=qux';
    window.location.search = '?foo=bar&baz=qux';
    const { result } = renderHook(() => useShowPageTabs(testTabAtom, 'first'), {
      jotaiAtoms: [
        [testTabAtom, 'first'],
        //
      ],
    });

    // ACT
    act(() => {
      result.current.setCurrentTab('second');
    });

    // ASSERT
    expect(router.replace).toHaveBeenCalledWith({
      url: 'https://retroachievements.org/page/1?foo=bar&baz=qux&tab=second',
      preserveScroll: true,
      preserveState: true,
    });
  });

  it('given there are existing query params including tab and the user sets the default tab, removes only the tab param', () => {
    // ARRANGE
    (window.location as any).href =
      'https://retroachievements.org/page/1?foo=bar&tab=second&baz=qux';
    window.location.search = '?foo=bar&tab=second&baz=qux';
    const { result } = renderHook(() => useShowPageTabs(testTabAtom, 'first'), {
      jotaiAtoms: [
        [testTabAtom, 'second'],
        //
      ],
    });

    // ACT
    act(() => {
      result.current.setCurrentTab('first');
    });

    // ASSERT
    expect(router.replace).toHaveBeenCalledWith({
      url: 'https://retroachievements.org/page/1?foo=bar&baz=qux',
      preserveScroll: true,
      preserveState: true,
    });
  });

  it('given shouldPushHistory is true, uses router.visit instead of router.replace', () => {
    // ARRANGE
    vi.spyOn(router, 'visit').mockImplementation(() => {});

    const { result } = renderHook(() => useShowPageTabs(testTabAtom, 'first'), {
      jotaiAtoms: [
        [testTabAtom, 'first'],
        //
      ],
    });

    // ACT
    act(() => {
      result.current.setCurrentTab('second', { shouldPushHistory: true });
    });

    // ASSERT
    expect(router.visit).toHaveBeenCalledWith('https://retroachievements.org/page/1?tab=second', {
      preserveScroll: true,
      preserveState: true,
    });
    expect(router.replace).not.toHaveBeenCalled();
  });

  it('given the URL has a tab param on mount, syncs the atom to match the URL', () => {
    // ARRANGE
    (window.location as any).href = 'https://retroachievements.org/page/1?tab=third';
    window.location.search = '?tab=third';

    // ACT
    const { result } = renderHook(() => useShowPageTabs(testTabAtom, 'first'), {
      jotaiAtoms: [
        [testTabAtom, 'first'],
        //
      ],
    });

    // ASSERT
    expect(result.current.currentTab).toEqual('third');
  });

  it('given a popstate event fires, syncs the atom to match the new URL', () => {
    // ARRANGE
    (window.location as any).href = 'https://retroachievements.org/page/1?tab=third';
    window.location.search = '?tab=third';

    const { result } = renderHook(() => useShowPageTabs(testTabAtom, 'first'), {
      jotaiAtoms: [
        [testTabAtom, 'first'],
        //
      ],
    });

    // ... sanity check - should have synced on mount ..
    expect(result.current.currentTab).toEqual('third');

    // ACT
    // ... simulate a browser back navigation by changing the URL and firing popstate ..
    (window.location as any).href = 'https://retroachievements.org/page/1';
    window.location.search = '';

    act(() => {
      window.dispatchEvent(new PopStateEvent('popstate'));
    });

    // ASSERT
    expect(result.current.currentTab).toEqual('first');
  });
});
