interface ReorderSiteAwardsState {
  autoscrollAnimationId: number | null;
  autoscrollDirection: number | null;
  currentGrabbedRowEl: HTMLTableRowElement | null;
  isFormDirty: boolean;
  manualMoveTimeoutId: NodeJS.Timeout | null;
}

export const reorderSiteAwardsStore: ReorderSiteAwardsState = {
  autoscrollAnimationId: null,
  autoscrollDirection: null,
  currentGrabbedRowEl: null,
  isFormDirty: false,
  manualMoveTimeoutId: null,
};
