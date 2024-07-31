import { tooltipStore as store } from '../state/tooltipStore';
import { updateTooltipPosition } from './updateTooltipPosition';

/**
 * Renders a tooltip with the given HTML content anchored to the specified element.
 *
 * This function creates a new tooltip element, applies the appropriate CSS classes,
 * sets its content to the provided HTML string, and positions it relative to the
 * anchor element. If `offsetX` and `offsetY` values are provided, the tooltip's position
 * is adjusted accordingly. The `options` parameter can be used to customize the appearance
 * of the tooltip.
 *
 * @param anchorEl The HTML element to anchor the tooltip to.
 * @param html The HTML content to be displayed in the tooltip.
 * @param offsetX Optional X-coordinate to adjust the tooltip's position.
 * @param offsetY Optional Y-coordinate to adjust the tooltip's position.
 * @param options Optional object containing additional configuration for the tooltip appearance.
 *                Currently supports the `isBorderless` property, which, if set to true, removes
 *                the border from the tooltip.
 */
export function renderTooltip(
  anchorEl: HTMLElement,
  html: string,
  offsetX?: number,
  offsetY?: number,
  options?: Partial<{ isBorderless: boolean }>,
) {
  if (store.tooltipEl !== null) {
    store.tooltipEl.remove();
    store.tooltipEl = null;
  }

  // If a dynamic tooltip is loading (eg: a user tooltip),
  // prevent it from finishing.
  store.dynamicTimeoutId = null;

  store.currentTooltipId = Math.random();
  store.tooltipEl = document.createElement('div');

  store.tooltipEl.classList.add(
    'animate-fade-in',
    'drop-shadow-2xl',
    'hidden',
    'w-max',
    'absolute',
    'top-0',
    'left-0',
    'rounded',
    'pointer-events-none',
    'overflow-hidden',
    'z-20',
  );

  if (!options?.isBorderless) {
    store.tooltipEl.classList.add('bg-embed-highlight');
  }

  store.tooltipEl.style.setProperty('width', 'max-content');
  store.tooltipEl.innerHTML = html;
  document.body.appendChild(store.tooltipEl);

  store.tooltipEl.style.display = 'block';

  updateTooltipPosition(
    anchorEl,
    store.tooltipEl,
    store.trackedMouseX + offsetX,
    store.trackedMouseY + offsetY,
  );
}
