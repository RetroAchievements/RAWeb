import { reorderSiteAwardsStore as store } from './reorderSiteAwardsStore';

export function cancelAutoscroll() {
  store.autoscrollDirection = null;
  if (store.autoscrollAnimationId) {
    cancelAnimationFrame(store.autoscrollAnimationId);
    store.autoscrollAnimationId = null;
  }
}
