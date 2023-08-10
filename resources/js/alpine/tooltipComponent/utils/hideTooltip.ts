import { tooltipStore as store } from '../state/tooltipStore';

/**
 * Hides the currently displayed tooltip.
 *
 * This function first checks if there's a dynamic tooltip loading timeout in
 * progress and clears it if found. Then, it applies a CSS transition to animate the
 * hiding of the currently-active tooltip. After the transition is finished, it
 * resets the tooltip element display, transform, and opacity properties so they
 * don't stick around in the DOM.
 */
export function hideTooltip() {
  // Retrieve the current tooltip metadata from the store.
  const activeTooltipId = store.currentTooltipId;
  const tooltipEl = store.tooltipEl;

  // If we're currently queued up to load a dynamic tooltip, it's safe
  // to go ahead and end that process. This also helps a bit with performance.
  if (store.dynamicTimeoutId) {
    clearTimeout(store.dynamicTimeoutId);
    store.dynamicTimeoutId = null;
  }

  // If we fall into this block, there's an active tooltip element in the
  // DOM and we're going to proceed with running an animation to fade it out.
  if (tooltipEl) {
    store.dynamicTimeoutId = null;

    tooltipEl.style.transition = 'opacity 150ms ease, transform 150ms ease';
    tooltipEl.style.opacity = '0';

    setTimeout(() => {
      if (tooltipEl && store.currentTooltipId === activeTooltipId) {
        tooltipEl.style.display = '';

        tooltipEl.style.removeProperty('transition');
        tooltipEl.style.removeProperty('opacity');
      }
    }, 150);
  }
}
