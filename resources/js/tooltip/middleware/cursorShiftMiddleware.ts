import { shift, type Middleware } from '@floating-ui/core';

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
        x: shiftResult.x,
        y: shiftResult.y,
      },
    };
  },
});
