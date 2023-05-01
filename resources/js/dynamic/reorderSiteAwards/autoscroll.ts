import { reorderSiteAwardsStore as store } from './reorderSiteAwardsStore';

export function autoscroll() {
  if (store.autoscrollDirection !== null) {
    const scrollSpeedFactor = Math.min(Math.abs(store.autoscrollDirection) * 50, 50);

    window.scrollBy(0, store.autoscrollDirection * scrollSpeedFactor);
    store.autoscrollAnimationId = requestAnimationFrame(autoscroll);
  }
}
