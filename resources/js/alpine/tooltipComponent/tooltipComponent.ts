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
            event.pageX,
            event.pageY,
          );
        } else if (props.staticHtmlContent) {
          renderTooltip(
            anchorEl,
            props.staticHtmlContent as string,
            event.pageX + 8,
            event.pageY + 6,
          );
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
    trackTooltipMouseMovement(anchorEl, event);
  };

  return {
    showTooltip,
    hideTooltip,
    trackMouseMovement,
  };
}
