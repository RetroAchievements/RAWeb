import { reorderSiteAwardsStore as store } from './reorderSiteAwardsStore';

/**
 * Autoscrolls the window based on the current autoscroll direction
 * stored in feature state. This function is designed to be called in
 * an animation loop using `requestAnimationFrame()` for improved performance.
 *
 * The scroll speed is determined by the absolute value of the stored
 * `autoscrollDirection`, with a maximum speed factor of 50 pixels per frame.
 */
export function autoscroll() {
  if (store.autoscrollDirection !== null) {
    // Calculate the scroll speed factor based on the autoscroll direction.
    const scrollSpeedFactor = Math.min(Math.abs(store.autoscrollDirection) * 50, 50);

    // Scroll the window by the calculated amount in the given direction.
    window.scrollBy(0, store.autoscrollDirection * scrollSpeedFactor);

    // Request the next frame of the animation loop and store the frame ID.
    store.autoscrollAnimationId = requestAnimationFrame(autoscroll);
  }
}
