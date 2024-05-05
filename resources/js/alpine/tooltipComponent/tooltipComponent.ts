import { tooltipStore as store } from './state/tooltipStore';
import { getIsMobileIos } from './utils/getIsMobileIos';
import { hideTooltip as internalHideTooltip } from './utils/hideTooltip';
import { loadDynamicTooltip } from './utils/loadDynamicTooltip';
import { renderTooltip } from './utils/renderTooltip';
import { trackTooltipMouseMovement } from './utils/trackTooltipMouseMovement';

interface TooltipProps {
  staticHtmlContent: string;
  dynamicType: string;
  dynamicId: string;
  dynamicContext: unknown;
}

/**
 * This component is used to add a tooltip to any element on the site.
 *
 * Depending on the provided options, the tooltip can either have static HTML content
 * or dynamic HTML content that is fetched from the server. On mobile iOS, this function
 * does nothing so as to prevent tooltips from blocking user interactions on the page.
 *
 * @param anchorEl The HTML element to which the tooltip should be attached.
 * @param props An object containing the tooltip configuration options.
 *                This can include the static HTML content or the dynamic type and ID
 *                used to fetch the content, as well as an optional context for dynamic tooltips.
 *
 * @example
 * ```html
 * <!-- Static content -->
 * <div
 *     x-data="tooltipComponent($el, { staticHtmlContent: '<p>I am tooltip content!</p>' }"
 *     @mouseover="showTooltip($event)"
 *     @mouseleave="hideTooltip"
 *     @mousemove="trackMouseMovement($event)"
 * >
 *     Static tooltipped Element
 * </div>
 *
 * <!-- Dynamic content -->
 * <div
 *     x-data="tooltipComponent($el, { dynamicType: 'game', dynamicId: '1', dynamicContext: 'context' }"
 *     @mouseover="showTooltip($event)"
 *     @mouseleave="hideTooltip"
 *     @mousemove="trackMouseMovement($event)"
 * >
 *     Dynamic tooltipped element
 * </div>
 * ```
 */
export function tooltipComponent(anchorEl: HTMLElement, props: Partial<TooltipProps>) {
  let isTooltipShowing = false;
  let showTimeout: number | null = null;

  const showTooltip = (event: MouseEvent) => {
    if (getIsMobileIos()) {
      return;
    }

    store.isHoveringOverAnchorEl = true;

    if (isTooltipShowing) {
      return;
    }

    store.trackedMouseX = event.pageX;
    store.trackedMouseY = event.pageY;

    showTimeout = window.setTimeout(() => {
      if (!isTooltipShowing && store.isHoveringOverAnchorEl) {
        if (props.dynamicType && props.dynamicId) {
          loadDynamicTooltip(
            anchorEl,
            props.dynamicType as string,
            props.dynamicId as string,
            props?.dynamicContext,
          );
        } else if (props.staticHtmlContent) {
          renderTooltip(anchorEl, props.staticHtmlContent as string, 8, 6);
        }

        isTooltipShowing = true;
      }
    }, 70);
  };

  const hideTooltip = () => {
    if (showTimeout) {
      clearTimeout(showTimeout);
      showTimeout = null;
    }

    isTooltipShowing = false;
    store.isHoveringOverAnchorEl = false;
    internalHideTooltip();
  };

  const trackMouseMovement = (event: MouseEvent) => {
    store.trackedMouseX = event.pageX;
    store.trackedMouseY = event.pageY;
    trackTooltipMouseMovement(anchorEl, event, props?.dynamicType ? 'dynamic' : 'static');
  };

  return {
    showTooltip,
    hideTooltip,
    trackMouseMovement,
  };
}
