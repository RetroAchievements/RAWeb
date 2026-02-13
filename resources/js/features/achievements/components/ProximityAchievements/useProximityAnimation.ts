import { router } from '@inertiajs/react';
import type { KeyboardEvent } from 'react';
import { useLayoutEffect, useRef } from 'react';

export const VISIBLE_COUNT = 5;

// Vertical inset (in px) between the indicator bar and the item edges.
const INDICATOR_INSET_PX = 8;

// Snappy deceleration so the indicator arrives slightly ahead of the list.
const INDICATOR_DURATION_MS = 250;
const INDICATOR_EASING = 'cubic-bezier(0.16, 1, 0.3, 1)';

const LIST_DURATION_MS = 300;
const LIST_EASING = 'cubic-bezier(0.22, 1, 0.36, 1)';

interface UseProximityAnimationProps {
  currentIndex: number;
  itemCount: number;

  /** Skip animation and navigate immediately (eg. on small screens). */
  shouldSkipAnimation?: boolean;
}

export function useProximityAnimation({
  currentIndex,
  itemCount,
  shouldSkipAnimation = false,
}: UseProximityAnimationProps) {
  const containerRef = useRef<HTMLDivElement>(null);
  const listRef = useRef<HTMLOListElement>(null);
  const indicatorRef = useRef<HTMLDivElement>(null);
  const itemRefs = useRef<(HTMLLIElement | null)[]>([]);
  const titleRefs = useRef<(HTMLParagraphElement | null)[]>([]);
  const hoverTimeoutRef = useRef<number>(undefined);
  const isNavigatingRef = useRef(false);
  const itemHeightRef = useRef(0);
  const containerHeightRef = useRef(0);

  // Measure one item's height pre-paint and derive all positions arithmetically.
  // This lets us avoid subpixel drift from cumulative offsetTop reads across items.
  useLayoutEffect(() => {
    if (itemCount === 0) {
      return;
    }

    const items = itemRefs.current;
    const container = containerRef.current;
    const list = listRef.current;
    const indicator = indicatorRef.current;
    if (!container || !list || !items[0]) {
      return;
    }

    // All items share a consistent height, so one height measurement will suffice.
    const itemHeight = Math.round(items[0].offsetHeight);
    itemHeightRef.current = itemHeight;

    for (const item of items) {
      if (item) {
        item.style.height = `${itemHeight}px`;
      }
    }

    const visibleHeight = itemHeight * Math.min(VISIBLE_COUNT, items.length);
    containerHeightRef.current = visibleHeight;

    if (items.length > VISIBLE_COUNT) {
      // +8px accounts for py-1 (4px top + 4px bottom) on the container.
      container.style.maxHeight = `${visibleHeight + 8}px`;
    }

    if (indicator && currentIndex >= 0) {
      const itemTop = currentIndex * itemHeight;

      indicator.style.transition = 'none';
      indicator.style.top = `${itemTop + INDICATOR_INSET_PX}px`;
      indicator.style.height = `${itemHeight - INDICATOR_INSET_PX * 2}px`;
      indicator.style.visibility = 'visible';

      const offset = computeScrollOffset(currentIndex, itemHeight, items.length, visibleHeight);
      list.style.transition = 'none';
      list.style.transform = `translateY(-${offset}px)`;
    }
  }, [itemCount, currentIndex]);

  const handleItemClick = (index: number, href: string) => {
    if (isNavigatingRef.current || index === currentIndex) {
      return;
    }

    isNavigatingRef.current = true;

    // On small screens the animation isn't visible, so just navigate immediately.
    if (shouldSkipAnimation) {
      router.visit(href);

      return;
    }

    const list = listRef.current;
    const indicator = indicatorRef.current;
    if (!list || !indicator) {
      return;
    }

    // Freeze pointer/hover state while the list translates so text-link-hover
    // doesn't oddly jump between rows as the rows move under the cursor.
    list.style.pointerEvents = 'none';

    // Capture label colors after hover is frozen and before animation writes.
    const prevTitle = titleRefs.current[currentIndex];
    const nextTitle = titleRefs.current[index];
    const defaultColor = prevTitle ? getComputedStyle(prevTitle).color : '';
    const linkColor = nextTitle ? getComputedStyle(nextTitle).color : '';

    // Animate the indicator to the clicked item.
    const itemHeight = itemHeightRef.current;
    const itemTop = index * itemHeight;
    indicator.style.transition = `top ${INDICATOR_DURATION_MS}ms ${INDICATOR_EASING}, height ${INDICATOR_DURATION_MS}ms ${INDICATOR_EASING}`;
    indicator.style.top = `${itemTop + INDICATOR_INSET_PX}px`;
    indicator.style.height = `${itemHeight - INDICATOR_INSET_PX * 2}px`;

    const offset = computeScrollOffset(
      index,
      itemHeight,
      itemRefs.current.length,
      containerHeightRef.current,
    );
    list.style.transition = `transform ${LIST_DURATION_MS}ms ${LIST_EASING}`;
    list.style.transform = `translateY(-${offset}px)`;

    // Cross-fade label text colors between the old and new current items.
    const colorTransition = `color ${INDICATOR_DURATION_MS}ms ${INDICATOR_EASING}`;
    if (prevTitle && linkColor) {
      prevTitle.style.transition = colorTransition;
      prevTitle.style.color = linkColor;
    }
    if (nextTitle && defaultColor) {
      nextTitle.style.transition = colorTransition;
      nextTitle.style.color = defaultColor;
    }

    // Only actually navigate once the indicator animation finishes.
    setTimeout(() => {
      router.visit(href);
    }, INDICATOR_DURATION_MS);
  };

  const handleItemKeyDown = (event: KeyboardEvent, index: number, href: string) => {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      handleItemClick(index, href);
    }
  };

  const handleItemMouseEnter = (href: string) => {
    // Only prefetch after a sustained hover to avoid firing requests as the user
    // scans through the list. Otherwise, the user may get rate-limited by nginx!
    hoverTimeoutRef.current = window.setTimeout(() => {
      router.prefetch(href, {}, { cacheFor: '30s' });
    }, 500);
  };

  const handleItemMouseLeave = () => {
    if (hoverTimeoutRef.current) {
      clearTimeout(hoverTimeoutRef.current);
    }
  };

  return {
    containerRef,
    listRef,
    indicatorRef,
    itemRefs,
    titleRefs,
    handleItemClick,
    handleItemKeyDown,
    handleItemMouseEnter,
    handleItemMouseLeave,
  };
}

/**
 * Computes the scroll offset needed to center `targetIndex` within the
 * visible window, clamped to [0, maxScrollable].
 */
function computeScrollOffset(
  targetIndex: number,
  itemHeight: number,
  totalItems: number,
  visibleHeight: number,
): number {
  const itemTop = targetIndex * itemHeight;
  const totalHeight = totalItems * itemHeight;
  const maxOffset = Math.max(0, totalHeight - visibleHeight);

  return Math.min(maxOffset, Math.max(0, itemTop - visibleHeight / 2 + itemHeight / 2));
}
