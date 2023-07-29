import { computePosition, offset as offsetMiddleware } from '@floating-ui/dom';

import { cursorFlipMiddleware } from '../middleware/cursorFlipMiddleware';
import { cursorShiftMiddleware } from '../middleware/cursorShiftMiddleware';

/**
 * Updates the position of the tooltip anchored to a specific DOM element.
 *
 * This function uses the `computePosition()` method from @floating-ui/dom to
 * calculate what the tooltip's absolute position should be. The position is
 * adjusted with various middleware functions to account for if it could
 * potentially be intersecting with the edges of the screen. The middlewares shift
 * the tooltip's position accordingly. Once the correct position has been calculated,
 * it is applied using an inline style on the tooltip DOM element.
 *
 * @param anchorEl The HTML element to anchor the tooltip to.
 * @param tooltipEl The tooltip HTML element whose position needs to be updated.
 * @param givenX Optional X-coordinate to adjust the tooltip's position.
 * @param givenY Optional Y-coordinate to adjust the tooltip's position.
 */
export function updateTooltipPosition(
  anchorEl: HTMLElement,
  tooltipEl: HTMLElement,
  givenX?: number,
  givenY?: number,
) {
  computePosition(anchorEl, tooltipEl, {
    placement: 'bottom-end',
    middleware: [
      offsetMiddleware(6),
      cursorFlipMiddleware(),
      cursorShiftMiddleware(givenX, givenY),
    ],
  }).then(async ({ x, y, middlewareData }) => {
    const setX = middlewareData.cursorShift.x ?? givenX ?? x;
    let setY = givenY ?? y;

    if (middlewareData.cursorFlip.isFlipped) {
      const tooltipHeight = tooltipEl.getBoundingClientRect().height;
      setY -= tooltipHeight + 12;
    }

    Object.assign(tooltipEl.style, {
      left: `${setX}px`,
      top: `${setY}px`,
    });
  });
}
