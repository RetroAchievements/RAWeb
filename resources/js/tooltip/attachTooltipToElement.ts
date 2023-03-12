import { hideTooltip } from './utils/hideTooltip';
import { loadDynamicTooltip } from './utils/loadDynamicTooltip';
import { renderTooltip } from './utils/renderTooltip';
import { trackTooltipMouseMovement } from './utils/trackTooltipMouseMovement';

export function attachTooltipToElement(
  anchorEl: HTMLElement,
  options: Partial<{
    staticHtmlContent: string;
    dynamicType: string;
    dynamicId: string;
    dynamicContext: unknown;
  }>
) {
  if (options?.dynamicType && options?.dynamicId) {
    const dynamicHtmlTooltipListeners = [
      [
        'mouseover',
        () => loadDynamicTooltip(
          anchorEl,
          options.dynamicType as string,
          options.dynamicId as string,
          options?.dynamicContext
        ),
      ],
      ['mouseleave', hideTooltip],
      ['mousemove', (event: MouseEvent) => trackTooltipMouseMovement(anchorEl, event)],
      ['focus', renderTooltip],
      ['blur', hideTooltip],
    ];

    dynamicHtmlTooltipListeners.forEach(([event, listenerFn]) => {
      anchorEl.addEventListener(event as keyof HTMLElementEventMap, listenerFn as EventListener);
    });
  } else if (options?.staticHtmlContent) {
    const staticHtmlTooltipListeners = [
      ['mouseover', () => renderTooltip(anchorEl, options?.staticHtmlContent ?? '')],
      ['mouseleave', hideTooltip],
      ['mousemove', (event: MouseEvent) => trackTooltipMouseMovement(anchorEl, event)],
      ['focus', renderTooltip],
      ['blur', hideTooltip],
    ];

    staticHtmlTooltipListeners.forEach(([event, listenerFn]) => {
      anchorEl.addEventListener(event as keyof HTMLElementEventMap, listenerFn as EventListener);
    });
  }
}
