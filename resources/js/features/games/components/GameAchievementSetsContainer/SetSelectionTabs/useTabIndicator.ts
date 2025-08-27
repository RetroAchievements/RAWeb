import { useEffect, useRef, useState } from 'react';

export function useTabIndicator(initialIndex: number) {
  const [activeIndex, setActiveIndex] = useState(Math.max(0, initialIndex));
  const [indicatorWidth, setIndicatorWidth] = useState(0);
  const [indicatorPosition, setIndicatorPosition] = useState(0);
  const [isAnimationReady, setIsAnimationReady] = useState(false);

  const tabRefs = useRef<(HTMLDivElement | null)[]>([]);

  useEffect(() => {
    const activeElement = tabRefs.current[activeIndex];
    if (activeElement) {
      setIndicatorPosition(activeElement.offsetLeft);
      setIndicatorWidth(activeElement.offsetWidth);

      // Enable transitions after first position is set.
      if (!isAnimationReady) {
        setTimeout(() => setIsAnimationReady(true), 50);
      }
    }
  }, [activeIndex, isAnimationReady]);

  const indicatorStyles = {
    transform: `translateX(${indicatorPosition}px)`,
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
