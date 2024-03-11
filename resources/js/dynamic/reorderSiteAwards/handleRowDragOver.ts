import { autoscroll } from './autoscroll';
import { cancelAutoscroll } from './cancelAutoscroll';
import { reorderSiteAwardsStore as store } from './reorderSiteAwardsStore';

export function handleRowDragOver(event: DragEvent) {
  event.preventDefault();

  // How many px away from the window edge until we start autoscrolling?
  const scrollWindowEdgeThreshold = 150;

  const cursorY = event.clientY;
  const windowHeight = window.innerHeight;

  // If the cursor is near the top edge of the screen, set autoscrollDirection to
  // negative and adjust speed based on the user's cursor distance from the edge.
  if (cursorY < scrollWindowEdgeThreshold) {
    store.autoscrollDirection =
      -1 * ((scrollWindowEdgeThreshold - cursorY) / scrollWindowEdgeThreshold);

    if (!store.autoscrollAnimationId) {
      store.autoscrollAnimationId = requestAnimationFrame(autoscroll);
    }
  } else if (cursorY > windowHeight - scrollWindowEdgeThreshold) {
    // If the cursor is near the bottom edge of the screen, set autoscrollDirection to
    // positive and adjust speed based on the user's cursor distance from the edge.
    store.autoscrollDirection =
      (cursorY - (windowHeight - scrollWindowEdgeThreshold)) / scrollWindowEdgeThreshold;

    if (!store.autoscrollAnimationId) {
      store.autoscrollAnimationId = requestAnimationFrame(autoscroll);
    }
  } else {
    // If the cursor is not near the edges of the screen,
    // set autoscrollDirection to null and stop scrolling.
    cancelAutoscroll();
  }

  return false;
}
