import { updateTooltipPosition } from './updateTooltipPosition';

export function pinTooltipToCursorPosition(
  anchorEl: HTMLElement,
  tooltipEl: HTMLElement | null,
  trackedMouseX: number | null,
  trackedMouseY: number | null
) {
  if (trackedMouseX && trackedMouseY && tooltipEl) {
    updateTooltipPosition(anchorEl, tooltipEl, trackedMouseX + 12, trackedMouseY + 16);
  }
}
