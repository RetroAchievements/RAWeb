import { updateTooltipPosition } from './updateTooltipPosition';

/**
 * Pins the tooltip to the current cursor position with a fixed offset for improved UX.
 *
 * This function positions the tooltip relative to the mouse cursor using a set of
 * provided tracked mouse coordinates. It adds a fixed offset to the X and Y coordinates,
 * ensuring the tooltip is not obstructing the view of the cursor or the element it is
 * hovering over.
 *
 * @param anchorEl The HTML element to anchor the tooltip to.
 * @param tooltipEl The tooltip HTMLElement to position near the cursor.
 * @param trackedMouseX The X-coordinate of the tracked mouse position.
 * @param trackedMouseY The Y-coordinate of the tracked mouse position.
 */
export function pinTooltipToCursorPosition(
  anchorEl: HTMLElement,
  tooltipEl: HTMLElement | null,
  trackedMouseX: number | null,
  trackedMouseY: number | null,
) {
  if (trackedMouseX && trackedMouseY && tooltipEl) {
    updateTooltipPosition(anchorEl, tooltipEl, trackedMouseX + 12, trackedMouseY + 16);
  }
}
