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

/**
 * Attaches the given tooltip event listeners to a specified anchor element.
 * Once the event listeners are attached, the tooltip can be considered "primed",
 * and it will react to user events, such as mouseenter.
 *
 * @param anchorEl The HTML element to which the tooltip listeners should be attached.
 * @param showFn The function to call when the tooltip should be shown.
 */
function attachTooltipListeners(
  anchorEl: HTMLElement,
  showFn: (givenX: number, givenY: number) => void
) {
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

/**
 * Attaches a tooltip to the specified HTML element with the given options.
 * This function is used to add a tooltip to any element on the site.
 *
 * Depending on the provided options, the tooltip can either have static HTML content
 * or dynamic HTML content that is fetched from the server. On mobile iOS, this function
 * does nothing so as to prevent tooltips from blocking user interactions on the page.
 *
 * @param anchorEl The HTML element to which the tooltip should be attached.
 * @param options An object containing the tooltip configuration options.
 *                This can include the static HTML content or the dynamic type and ID
 *                used to fetch the content, as well as an optional context for dynamic tooltips.
 *
 * @example
 * ```html
 * <!-- Static content -->
 * <div
 *     x-init="attachTooltipToElement($el, { staticHtmlContent: '<p>I am tooltip content!</p>' }"
 * >
 *     Static tooltipped Element
 * </div>
 *
 * <!-- Dynamic content -->
 * <div
 *     x-init="attachTooltipToElement($el, { dynamicType: 'game', dynamicId: '1', dynamicContext: 'context' }"
 * >
 *     Dynamic tooltipped element
 * </div>
 * ```
 */
export function attachTooltipToElement(anchorEl: HTMLElement, options: Partial<TooltipOptions>) {
  // Tooltips can block the page on mobile iOS.
  if (getIsMobileIos()) {
    return;
  }

  // Do we need to dynamically fetch this tooltip's contents?
  if (options.dynamicType && options.dynamicId) {
    const showDynamicTooltip = (windowX: number, windowY: number) => loadDynamicTooltip(
      anchorEl,
      options.dynamicType as string,
      options.dynamicId as string,
      options?.dynamicContext,
      windowX,
      windowY,
    );

    attachTooltipListeners(anchorEl, showDynamicTooltip);
  } else if (options.staticHtmlContent) {
    const showStaticTooltip = (windowX: number, windowY: number) => renderTooltip(
      anchorEl,
      options.staticHtmlContent as string,
      windowX + 8,
      windowY + 6,
    );

    attachTooltipListeners(anchorEl, showStaticTooltip);
  }
}
