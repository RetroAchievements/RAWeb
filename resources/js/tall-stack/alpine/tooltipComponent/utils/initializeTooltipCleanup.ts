import { router } from '@inertiajs/react';

import { tooltipStore } from '../state/tooltipStore';
import { hideTooltip } from './hideTooltip';

/**
 * Initializes tooltip cleanup on Inertia navigation.
 * This should be called once when the app initializes.
 * This prevents orphaned tooltips from lingering on the page during Inertia router transitions.
 */
export function initializeTooltipCleanup() {
  // Skip if we're in SSR environment.
  if (typeof window === 'undefined') {
    return;
  }

  const handleNavigationStart = () => {
    // First, properly hide any active tooltip (this clears dynamic timeouts).
    if (tooltipStore.tooltipEl || tooltipStore.dynamicTimeoutId) {
      hideTooltip();
    }

    // Clear any pending dynamic tooltip timeouts explicitly.
    if (tooltipStore.dynamicTimeoutId) {
      clearTimeout(tooltipStore.dynamicTimeoutId);
      tooltipStore.dynamicTimeoutId = null;
    }

    // Query and remove all tooltip elements by their data attribute as a safety net.
    const tooltips = document.querySelectorAll('[data-alpine-tooltip]');
    for (const tooltip of tooltips) {
      tooltip.remove();
    }

    // Clear all tooltip store state.
    tooltipStore.tooltipEl = null;
    tooltipStore.currentTooltipId = null;
    tooltipStore.isHoveringOverAnchorEl = false;
    tooltipStore.activeAnchorEl = null;
  };

  // Listen for navigation events.
  router.on('before', handleNavigationStart);
  router.on('success', handleNavigationStart);
}
