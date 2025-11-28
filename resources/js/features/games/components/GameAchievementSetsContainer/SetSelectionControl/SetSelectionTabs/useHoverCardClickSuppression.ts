import { useRef, useState } from 'react';

interface UseHoverCardClickSuppressionOptions {
  onTabChange?: (index: number) => void;
}

/**
 * When a user clicks on a set selection tab, it auto-closes the hover card.
 * If they don't move their mouse, the hover card automatically reopens.
 * This can be kinda disorienting, we don't want to reopen the hover card.
 *
 * This hook is designed to hold some state to prevent that from happening,
 * which works in combination with InertiaLink's `preserveState` prop.
 */
export function useHoverCardClickSuppression(options?: UseHoverCardClickSuppressionOptions) {
  /**
   * Track which hover card is currently open, by index.
   */
  const [openHoverCard, setOpenHoverCard] = useState<number | null>(null);

  /**
   * Track tabs that were clicked and shouldn't reopen until the mouse pointer leaves.
   */
  const clickSuppressedTabs = useRef(new Set<number>());

  /**
   * Track timeouts for clearing suppression per tab index.
   */
  const suppressionTimeouts = useRef(new Map<number, NodeJS.Timeout>());

  const handleHoverCardOpenChange = (index: number, isOpen: boolean) => {
    // Don't allow reopening if this tab was just clicked.
    if (isOpen && clickSuppressedTabs.current.has(index)) {
      return;
    }

    setOpenHoverCard(isOpen ? index : null);
  };

  const handleTabClick = (index: number) => {
    // Suppress the hover card reopening until mouse leaves.
    clickSuppressedTabs.current.add(index);

    // Clear any existing timeout for this tab.
    const existingTimeout = suppressionTimeouts.current.get(index);
    if (existingTimeout) {
      clearTimeout(existingTimeout);
      suppressionTimeouts.current.delete(index);
    }

    // Then, close the hover card.
    setOpenHoverCard(null);

    // Finally, execute the caller's tab change logic.
    options?.onTabChange?.(index);
  };

  const handlePointerLeave = (index: number) => {
    // Delay clearing the suppression to allow navigation and a re-render to complete.
    // This prevents the hover card from reopening if the user clicks and quickly moves away.
    const timeout = setTimeout(() => {
      clickSuppressedTabs.current.delete(index);
      suppressionTimeouts.current.delete(index);
    }, 500);

    suppressionTimeouts.current.set(index, timeout);
  };

  return {
    handleHoverCardOpenChange,
    handlePointerLeave,
    handleTabClick,
    openHoverCard,
  };
}
