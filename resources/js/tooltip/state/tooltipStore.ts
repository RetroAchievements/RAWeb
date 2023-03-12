interface TooltipState {
  currentTooltipId: number | null;
  dynamicContentCache: Record<string, string>;
  tooltipEl: HTMLElement | null;
  trackedMouseX: number | null;
  trackedMouseY: number | null;
}

export const tooltipStore: TooltipState = {
  currentTooltipId: null,
  dynamicContentCache: {},
  tooltipEl: null,
  trackedMouseX: null,
  trackedMouseY: null
};
