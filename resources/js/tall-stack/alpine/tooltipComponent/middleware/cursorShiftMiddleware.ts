import { type Middleware, shift } from '@floating-ui/core';

/**
 * A middleware for shifting a Floating UI element along the X-axis if
 * there is not enough space to fit it in the viewport. This middleware helps
 * to avoid tooltip content overflowing outside of the browser window.
 */
export const cursorShiftMiddleware = (currentX?: number, currentY?: number): Middleware => ({
  name: 'cursorShift',
  async fn(state) {
    const shiftResult = await shift().fn({
      ...state,
      x: currentX ?? state.x,
      y: currentY ?? state.y,
    });

    return {
      data: {
        x: shiftResult?.x ?? state.x,
        y: shiftResult?.y ?? state.y,
      },
    };
  },
});
