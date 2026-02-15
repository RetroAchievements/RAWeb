import { router } from '@inertiajs/react';
import type { KeyboardEvent } from 'react';
import { useLayoutEffect, useRef, useState } from 'react';

export const VISIBLE_COUNT = 5;

// Visual breathing room so the indicator bar doesn't touch the item edges.
const INDICATOR_INSET_PX = 8;

// Snappy deceleration so the indicator arrives slightly ahead of the list.
const INDICATOR_DURATION_MS = 250;
const INDICATOR_EASING = 'cubic-bezier(0.16, 1, 0.3, 1)';

const LIST_DURATION_MS = 300;
const LIST_EASING = 'cubic-bezier(0.22, 1, 0.36, 1)';

interface UseProximityAnimationProps {
  currentIndex: number;
  itemCount: number;

  /** Skip animation and navigate immediately (eg. on small screens where the sidebar isn't visible). */
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
  const visibleStartRef = useRef(0);
  const visibleEndRef = useRef(0);

  // Start focus on the current achievement so the user's keyboard context matches what they see.
  const [focusedIndex, setFocusedIndex] = useState(Math.max(0, currentIndex));

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

    // One measurement suffices because all items share a consistent height.
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

    // Compute the scroll position and clamp the keyboard-navigable
    // range so arrow keys can't escape the visible window.
    const offset =
      currentIndex >= 0
        ? computeScrollOffset(currentIndex, itemHeight, items.length, visibleHeight)
        : 0;

    if (items.length <= VISIBLE_COUNT) {
      visibleStartRef.current = 0;
      visibleEndRef.current = items.length - 1;
    } else {
      const visibleStart = Math.round(offset / itemHeight);
      visibleStartRef.current = visibleStart;
      visibleEndRef.current = Math.min(visibleStart + VISIBLE_COUNT - 1, items.length - 1);
    }

    if (indicator && currentIndex >= 0) {
      const itemTop = currentIndex * itemHeight;

      indicator.style.transition = 'none';
      indicator.style.top = `${itemTop + INDICATOR_INSET_PX}px`;
      indicator.style.height = `${itemHeight - INDICATOR_INSET_PX * 2}px`;
      indicator.style.visibility = 'visible';

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

    // Read colors now, after hover is frozen, so the cross-fade starts from stable values.
    const prevTitle = titleRefs.current[currentIndex];
    const nextTitle = titleRefs.current[index];
    const defaultColor = prevTitle ? getComputedStyle(prevTitle).color : '';
    const linkColor = nextTitle ? getComputedStyle(nextTitle).color : '';

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

    // Smoothly swap the link/default text colors so the old "current" fades out and the new one fades in.
    const colorTransition = `color ${INDICATOR_DURATION_MS}ms ${INDICATOR_EASING}`;
    if (prevTitle && linkColor) {
      prevTitle.style.transition = colorTransition;
      prevTitle.style.color = linkColor;
    }
    if (nextTitle && defaultColor) {
      nextTitle.style.transition = colorTransition;
      nextTitle.style.color = defaultColor;
    }

    // Delay navigation so the user sees the indicator arrive at the target item.
    setTimeout(() => {
      router.visit(href);
    }, INDICATOR_DURATION_MS);
  };

  const handleItemKeyDown = (event: KeyboardEvent, index: number, href: string) => {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      handleItemClick(index, href);

      return;
    }

    // Clamp navigation to the visible window so arrow keys
    // can't reach items that are only loaded for animation.
    const visibleStart = visibleStartRef.current;
    const visibleEnd = visibleEndRef.current;

    let targetIndex: number | null = null;
    switch (event.key) {
      case 'ArrowDown':
        targetIndex = Math.min(index + 1, visibleEnd);
        break;

      case 'ArrowUp':
        targetIndex = Math.max(index - 1, visibleStart);
        break;

      case 'Home':
        targetIndex = visibleStart;
        break;

      case 'End':
        targetIndex = visibleEnd;
        break;
    }

    if (targetIndex !== null && targetIndex !== index) {
      event.preventDefault();

      setFocusedIndex(targetIndex);
      itemRefs.current[targetIndex]?.focus();
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
    focusedIndex,
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
