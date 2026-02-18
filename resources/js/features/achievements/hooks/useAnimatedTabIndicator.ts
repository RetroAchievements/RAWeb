import {
  type CSSProperties,
  type RefObject,
  useCallback,
  useEffect,
  useRef,
  useState,
} from 'react';
import { useWindowSize } from 'react-use';

// BaseTabsList uses h-10 (40px), matching the trigger's offsetHeight.
const ESTIMATED_TAB_HEIGHT = 40;

const FADE_TRANSITION = 'opacity 150ms ease-out';
const SLIDE_TRANSITION = 'all 150ms cubic-bezier(0.32, 0.72, 0, 1)';

interface ActiveMeasurements {
  left: number;
  width: number;
  top: number;
}

export function useAnimatedTabIndicator(initialIndex: number = 0) {
  const safeIndex = Math.max(0, initialIndex);

  const [activeIndex, setActiveIndex] = useState(safeIndex);
  const [hoveredIndex, setHoveredIndex] = useState<number | null>(null);
  const [isAnimationReady, setIsAnimationReady] = useState(false);

  // Start invisible so we don't flash an incorrectly-sized
  // indicator before the first real DOM measurement.
  const [active, setActive] = useState<ActiveMeasurements>({
    left: 0,
    width: 0,
    top: ESTIMATED_TAB_HEIGHT,
  });

  const tabRefs = useRef<(HTMLElement | null)[]>([]);
  const hoverIndicatorRef = useRef<HTMLDivElement>(null);
  const prevHoveredRef = useRef<number | null>(null);
  const hoverLeaveTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const { width: windowWidth } = useWindowSize();

  // Debounce null values so moving between tabs doesn't
  // briefly reset the hover state and break the slide animation.
  const setHoveredIndexDebounced = useCallback((index: number | null) => {
    if (hoverLeaveTimerRef.current) {
      clearTimeout(hoverLeaveTimerRef.current);
      hoverLeaveTimerRef.current = null;
    }

    if (index !== null) {
      setHoveredIndex(index);
    } else {
      hoverLeaveTimerRef.current = setTimeout(() => {
        setHoveredIndex(null);
      }, 75);
    }
  }, []);

  // Clean up the debounce timer on unmount.
  useEffect(() => {
    return () => {
      if (hoverLeaveTimerRef.current) {
        clearTimeout(hoverLeaveTimerRef.current);
      }
    };
  }, []);

  // Sync the active indicator with the active tab element.
  useEffect(() => {
    const el = tabRefs.current[activeIndex];
    if (el) {
      setActive({
        left: el.offsetLeft,
        width: el.offsetWidth,
        top: el.offsetTop + el.offsetHeight,
      });

      // Delay enabling transitions so the initial position doesn't animate in.
      if (!isAnimationReady) {
        const timeoutId = setTimeout(() => setIsAnimationReady(true), 50);

        return () => clearTimeout(timeoutId);
      }
    }
  }, [activeIndex, isAnimationReady, windowWidth]);

  // Position the hover indicator via direct DOM manipulation to avoid
  // the React state update race that causes the slide-from-origin bug.
  // The transition style is set before the position, so the browser
  // knows whether to animate the change in the same frame.
  useEffect(() => {
    const hoverEl = hoverIndicatorRef.current;
    if (!hoverEl) {
      return;
    }

    if (hoveredIndex !== null) {
      const tabEl = tabRefs.current[hoveredIndex];
      if (tabEl) {
        // Only animate position when sliding between tabs, not when first appearing.
        const isSliding = prevHoveredRef.current !== null;

        hoverEl.style.transition = isSliding ? SLIDE_TRANSITION : FADE_TRANSITION;
        hoverEl.style.transform = `translateX(${tabEl.offsetLeft}px) translateY(${tabEl.offsetTop}px)`;
        hoverEl.style.width = `${tabEl.offsetWidth}px`;
        hoverEl.style.height = `${tabEl.offsetHeight}px`;
        hoverEl.style.opacity = '1';
      }
    } else {
      hoverEl.style.transition = FADE_TRANSITION;
      hoverEl.style.opacity = '0';
    }

    prevHoveredRef.current = hoveredIndex;
  }, [hoveredIndex, windowWidth]);

  const activeIndicatorStyles: CSSProperties = {
    transform: `translateX(${active.left}px) translateY(${active.top}px)`,
    width: `${active.width}px`,
    opacity: active.width ? 1 : 0,
    contain: 'layout',
  };

  return {
    activeIndex,
    setActiveIndex,
    hoveredIndex,
    tabRefs,
    activeIndicatorStyles,
    isAnimationReady,
    setHoveredIndex: setHoveredIndexDebounced,
    hoverIndicatorRef: hoverIndicatorRef as RefObject<HTMLDivElement>,
  };
}
