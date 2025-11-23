import { useEffect, useRef, useState } from 'react';
import { useWindowSize } from 'react-use';

export function useTabIndicator(initialIndex: number) {
  const [activeIndex, setActiveIndex] = useState(Math.max(0, initialIndex));
  const [indicatorWidth, setIndicatorWidth] = useState(0);
  const [indicatorPosition, setIndicatorPosition] = useState(0);
  const [indicatorTop, setIndicatorTop] = useState(0);
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
      setIndicatorTop(currentTop + currentHeight + 6); // position the indicator at bottom of the tab plus a 6px gap

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
