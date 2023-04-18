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

function attachTooltipListeners(anchorEl: HTMLElement, showFn: (givenX: number, givenY: number) => void) {
  let showTimeout: number | null = null;
  let lastMouseCoords: { x: number; y: number } | null = null;

  const handleMouseOver = () => {
    showTimeout = window.setTimeout(() => {
      showFn(lastMouseCoords?.x ?? 0, lastMouseCoords?.y ?? 0);
    }, 70);
  };

  const handleMouseLeave = () => {
    if (showTimeout) {
      clearTimeout(showTimeout);
      showTimeout = null;
    }

    hideTooltip();
  };

  const handleMouseMove = (event: MouseEvent) => {
    lastMouseCoords = { x: event.pageX, y: event.pageY };
    trackTooltipMouseMovement(anchorEl, event);
  };

  const tooltipListeners = [
    ['mouseover', handleMouseOver],
    ['mouseleave', handleMouseLeave],
    ['mousemove', handleMouseMove],
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
    const showDynamicTooltip = (windowX: number, windowY: number) => (
      loadDynamicTooltip(anchorEl, options.dynamicType as string, options.dynamicId as string, options?.dynamicContext, windowX, windowY)
    );

    attachTooltipListeners(anchorEl, showDynamicTooltip);
  } else if (options.staticHtmlContent) {
    const showStaticTooltip = (windowX: number, windowY: number) => (
      renderTooltip(anchorEl, options.staticHtmlContent as string, windowX + 8, windowY + 6)
    );

    attachTooltipListeners(anchorEl, showStaticTooltip);
  }
}
