interface TooltipState {
  currentTooltipId: number | null;
  dynamicContentCache: Record<string, string>;
  dynamicTimeoutId: NodeJS.Timeout | null;
  tooltipEl: HTMLElement | null;
  trackedMouseX: number | null;
  trackedMouseY: number | null;
}

export const tooltipStore: TooltipState = {
  currentTooltipId: null,
  dynamicContentCache: {},
  dynamicTimeoutId: null,
  tooltipEl: null,
  trackedMouseX: null,
  trackedMouseY: null
};
