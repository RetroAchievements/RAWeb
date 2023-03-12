import { computePosition, offset as offsetMiddleware } from '@floating-ui/dom';

import { cursorFlipMiddleware } from '../middleware/cursorFlipMiddleware';
import { cursorShiftMiddleware } from '../middleware/cursorShiftMiddleware';

export function updateTooltipPosition(
  anchorEl: HTMLElement,
  tooltipEl: HTMLElement,
  givenX?: number,
  givenY?: number
) {
  computePosition(anchorEl, tooltipEl, {
    placement: 'bottom-end',
    middleware: [offsetMiddleware(6), cursorFlipMiddleware(), cursorShiftMiddleware(givenX, givenY)],
  }).then(async ({ x, y, middlewareData }) => {
    const setX = middlewareData.cursorShift.x ?? givenX ?? x;
    let setY = givenY ?? y;

    if (middlewareData.cursorFlip.isFlipped) {
      const tooltipHeight = tooltipEl.getBoundingClientRect().height;
      setY -= tooltipHeight - 12;
    }

    Object.assign(tooltipEl.style, {
      left: `${setX}px`,
      top: `${setY}px`,
    });
  });
}
