import { tooltipStore as store } from '../state/tooltipStore';
import { updateTooltipPosition } from './updateTooltipPosition';

export function renderTooltip(
  anchorEl: HTMLElement,
  html: string,
  givenX?: number,
  givenY?: number,
  options?: Partial<{ isBorderless: boolean }>
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
    'rounded'
  );

  if (!options?.isBorderless) {
    store.tooltipEl.classList.add('border', 'border-embed-highlight');
  }

  store.tooltipEl.style.setProperty('width', 'max-content');
  store.tooltipEl.innerHTML = html;
  document.body.appendChild(store.tooltipEl);

  store.tooltipEl.style.display = 'block';

  updateTooltipPosition(anchorEl, store.tooltipEl, givenX, givenY);
}
