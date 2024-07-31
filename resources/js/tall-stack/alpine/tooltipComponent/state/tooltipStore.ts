interface TooltipState {
  activeAnchorEl: HTMLElement | null;
  currentTooltipId: number | null;
  dynamicContentCache: Record<string, string>;
  dynamicTimeoutId: NodeJS.Timeout | null;
  isHoveringOverAnchorEl: boolean;
  tooltipEl: HTMLElement | null;
  trackedMouseX: number | null;
  trackedMouseY: number | null;
}

export const tooltipStore: TooltipState = {
  activeAnchorEl: null,
  currentTooltipId: null,
  dynamicContentCache: {},
  dynamicTimeoutId: null,
  isHoveringOverAnchorEl: false,
  tooltipEl: null,
  trackedMouseX: null,
  trackedMouseY: null,
};
