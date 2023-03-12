import { tooltipStore as store } from '../state/tooltipStore';

export function hideTooltip() {
  const activeTooltipId = store.currentTooltipId;
  const tooltipEl = store.tooltipEl;

  if (tooltipEl) {
    tooltipEl.style.transition = 'opacity 200ms ease, transform 200ms ease';
    tooltipEl.style.opacity = '0';
    tooltipEl.style.transform = 'scale(0.95)';

    setTimeout(() => {
      if (tooltipEl && store.currentTooltipId === activeTooltipId) {
        tooltipEl.style.display = '';

        tooltipEl.style.removeProperty('transition');
        tooltipEl.style.removeProperty('transform');
        tooltipEl.style.removeProperty('opacity');
      }
    }, 200);
  }
}
