import { getIsMobileIos } from './utils/getIsMobileIos';
import { hideTooltip } from './utils/hideTooltip';
import { loadDynamicTooltip } from './utils/loadDynamicTooltip';
import { renderTooltip } from './utils/renderTooltip';
import { trackTooltipMouseMovement } from './utils/trackTooltipMouseMovement';

interface TooltipOptions {
  staticHtmlContent: string;
  dynamicType: string;
  dynamicId: string;
  dynamicContext: unknown;
}

function attachTooltipListeners(anchorEl: HTMLElement, showFn: () => void) {
  const tooltipListeners = [
    ['mouseover', showFn],
    ['mouseleave', hideTooltip],
    ['mousemove', (event: MouseEvent) => trackTooltipMouseMovement(anchorEl, event)],
  ];

  tooltipListeners.forEach(([event, listenerFn]) => {
    anchorEl.addEventListener(event as keyof HTMLElementEventMap, listenerFn as EventListener);
  });
}

export function attachTooltipToElement(
  anchorEl: HTMLElement,
  options: Partial<TooltipOptions>
) {
  // Tooltips can block the page on mobile iOS.
  if (getIsMobileIos()) {
    return;
  }

  // Do we need to dynamically fetch this tooltip's contents?
  if (options.dynamicType && options.dynamicId) {
    const showDynamicTooltip = () => (
      loadDynamicTooltip(anchorEl, options.dynamicType as string, options.dynamicId as string, options?.dynamicContext)
    );

    attachTooltipListeners(anchorEl, showDynamicTooltip);
  } else if (options.staticHtmlContent) {
    const showStaticTooltip = () => renderTooltip(anchorEl, options.staticHtmlContent as string);
    attachTooltipListeners(anchorEl, showStaticTooltip);
  }
}
