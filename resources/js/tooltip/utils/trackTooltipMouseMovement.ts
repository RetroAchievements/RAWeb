import { tooltipStore as store } from '../state/tooltipStore';
import { updateTooltipPosition } from './updateTooltipPosition';

export function trackTooltipMouseMovement(
  anchorEl: HTMLElement,
  event: MouseEvent
) {
  const tooltipEl = store.tooltipEl;

  store.trackedMouseX = event.pageX;
  store.trackedMouseY = event.pageY;

  if (tooltipEl) {
    updateTooltipPosition(
      anchorEl,
      tooltipEl,
      store.trackedMouseX + 12,
      store.trackedMouseY + 6,
    );
  }
}
