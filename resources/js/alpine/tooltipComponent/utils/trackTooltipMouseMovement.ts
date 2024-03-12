import { tooltipStore as store } from '../state/tooltipStore';
import { updateTooltipPosition } from './updateTooltipPosition';

/**
 * Tracks the user's mouse movement and updates the tooltip position accordingly.
 *
 * This function updates the user's tracked mouse coordinates based on the
 * MouseEvent provided and repositions the tooltip accordingly. The tooltip is
 * updated relative to the mouse cursor with a fixed offset to improve UX, such that
 * the tooltip is not obstructing the cursor or its anchor element.
 *
 * @param anchorEl The HTML element to anchor the tooltip to.
 * @param event The MouseEvent object containing the current mouse coordinates.
 * @param tooltipKind "static" if not lazy-loaded, "dynamic" if lazy-loaded.
 */
export function trackTooltipMouseMovement(
  anchorEl: HTMLElement,
  event: MouseEvent,
  tooltipKind: 'static' | 'dynamic',
) {
  const tooltipEl = store.tooltipEl;

  store.trackedMouseX = event.pageX;
  store.trackedMouseY = event.pageY;

  if (
    tooltipEl &&
    (tooltipKind === 'static' || (tooltipKind === 'dynamic' && anchorEl === store.activeAnchorEl))
  ) {
    updateTooltipPosition(anchorEl, tooltipEl, store.trackedMouseX + 12, store.trackedMouseY + 6);
  }
}
