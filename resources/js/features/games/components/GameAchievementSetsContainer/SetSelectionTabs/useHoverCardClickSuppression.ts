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

    // Then, close the hover card.
    setOpenHoverCard(null);

    // Finally, execute the caller's tab change logic.
    options?.onTabChange?.(index);
  };

  const handlePointerLeave = (index: number) => {
    // Clear suppression so the hover card can reopen on the next mouse cursor hover.
    clickSuppressedTabs.current.delete(index);
  };

  return {
    handleHoverCardOpenChange,
    handlePointerLeave,
    handleTabClick,
    openHoverCard,
  };
}
