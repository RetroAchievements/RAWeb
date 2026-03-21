import { describe, expect, it, vi } from 'vitest';

import { act, renderHook } from '@/test';

import type { TabConfig } from '../models';
import { useAchievementShowTabs } from './useAchievementShowTabs';

const mockSetCurrentTab = vi.fn();
const mockSetActiveIndex = vi.fn();
const mockSetHoveredIndex = vi.fn();

vi.mock('@/common/hooks/useShowPageTabs', () => ({
  useShowPageTabs: vi.fn(() => ({
    currentTab: 'comments',
    setCurrentTab: mockSetCurrentTab,
  })),
}));

vi.mock('./useAnimatedTabIndicator', () => ({
  useAnimatedTabIndicator: vi.fn(() => ({
    activeIndex: 0,
    setActiveIndex: mockSetActiveIndex,
    setHoveredIndex: mockSetHoveredIndex,
    activeIndicatorStyles: {},
    isAnimationReady: true,
    hoveredIndex: null,
    tabRefs: { current: [] },
    hoverIndicatorRef: { current: null },
  })),
}));

const tabConfigs: TabConfig[] = [
  { value: 'comments', label: 'Comments' },
  { value: 'unlocks', label: 'Unlocks' },
  { value: 'changelog', label: 'Changelog' },
];

describe('Hook: useAchievementShowTabs', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useAchievementShowTabs(tabConfigs));

    // ASSERT
    expect(result.current).toBeTruthy();
  });

  it('returns the correct initial properties', () => {
    // ARRANGE
    const { result } = renderHook(() => useAchievementShowTabs(tabConfigs));

    // ASSERT
    expect(result.current.currentTab).toEqual('comments');
    expect(result.current.activeIndex).toEqual(0);
    expect(typeof result.current.handleValueChange).toEqual('function');
    expect(typeof result.current.setHoveredIndex).toEqual('function');
  });

  it('given a valid tab value, calls setActiveIndex with the correct index', () => {
    // ARRANGE
    const { result } = renderHook(() => useAchievementShowTabs(tabConfigs));

    // ACT
    act(() => {
      result.current.handleValueChange('unlocks');
    });

    // ASSERT
    expect(mockSetActiveIndex).toHaveBeenCalledWith(1);
    expect(mockSetCurrentTab).toHaveBeenCalledWith('unlocks');
  });

  it('given an invalid tab value, does not call setActiveIndex', () => {
    // ARRANGE
    const { result } = renderHook(() => useAchievementShowTabs(tabConfigs));

    // ACT
    act(() => {
      result.current.handleValueChange('invalid-tab');
    });

    // ASSERT
    expect(mockSetActiveIndex).not.toHaveBeenCalled();
    expect(mockSetCurrentTab).toHaveBeenCalledWith('invalid-tab');
  });

  it('given the changelog tab, calls setActiveIndex with index 2', () => {
    // ARRANGE
    const { result } = renderHook(() => useAchievementShowTabs(tabConfigs));

    // ACT
    act(() => {
      result.current.handleValueChange('changelog');
    });

    // ASSERT
    expect(mockSetActiveIndex).toHaveBeenCalledWith(2);
    expect(mockSetCurrentTab).toHaveBeenCalledWith('changelog');
  });
});
