import { useEffect, useRef, useState } from 'react';
import { useWindowSize } from 'react-use';

/**
 * These constants match the tab styling in SetSelectionTabs.tsx.
 * They're used to provide reasonable SSR defaults so the indicator is
 * actually visible before hydration, otherwise it's obvious when hydration/JS
 * have actually kicked in due to a tiny flash of unstyled content.
 */
const TAB_IMAGE_SIZE = 32; // size-8
const TAB_PADDING_X = 8; // px-2
const TAB_GAP = 6; // gap-x-[6px] between tabs
const DEFAULT_WIDTH = TAB_IMAGE_SIZE + TAB_PADDING_X * 2; // 48px
const DEFAULT_TOP = 40; // offsetHeight (~34px) + TAB_GAP

export function useTabIndicator(initialIndex: number) {
  const safeIndex = Math.max(0, initialIndex);

  // Estimate the X position for any tab based on index.
  // Each tab is ~48px wide with 6px gap, so the position is index * (48 + 6).
  const estimatedPosition = safeIndex * (DEFAULT_WIDTH + TAB_GAP);

  const [activeIndex, setActiveIndex] = useState(safeIndex);
  const [indicatorWidth, setIndicatorWidth] = useState(DEFAULT_WIDTH);
  const [indicatorPosition, setIndicatorPosition] = useState(estimatedPosition);
  const [indicatorTop, setIndicatorTop] = useState(DEFAULT_TOP);
  const [isAnimationReady, setIsAnimationReady] = useState(false);

  const tabRefs = useRef<(HTMLDivElement | null)[]>([]);
  const { width } = useWindowSize(); // track window width for browser resize handling

  useEffect(() => {
    const activeElement = tabRefs.current[activeIndex];
    if (activeElement) {
      const currentTop = activeElement.offsetTop;
      const currentLeft = activeElement.offsetLeft;
      const currentWidth = activeElement.offsetWidth;
      const currentHeight = activeElement.offsetHeight;

      setIndicatorPosition(currentLeft);
      setIndicatorWidth(currentWidth);
      setIndicatorTop(currentTop + currentHeight + TAB_GAP);

      // Enable transitions after first position is set.
      if (!isAnimationReady) {
        setTimeout(() => setIsAnimationReady(true), 50);
      }
    }
  }, [activeIndex, isAnimationReady, width]);

  const indicatorStyles = {
    transform: `translateX(${indicatorPosition}px) translateY(${indicatorTop}px)`,
    width: `${indicatorWidth}px`,
    opacity: indicatorWidth ? 1 : 0,
    contain: 'layout' as const, // isolate this element from layout recalculations.
  };

  return {
    activeIndex,
    setActiveIndex,
    tabRefs,
    indicatorStyles,
    isAnimationReady,
  };
}
