import { flip, type Middleware } from '@floating-ui/dom';

/**
 * A middleware for flipping a Floating UI element along the primary axis if
 * there is not enough space to fit it in the viewport. This middleware helps
 * to avoid tooltip content overflowing outside of the browser window.
 */
export const cursorFlipMiddleware = (): Middleware => ({
  name: 'cursorFlip',
  async fn(state) {
    const flipResult = await flip({
      crossAxis: false,
      fallbackAxisSideDirection: 'start',
    }).fn({
      ...state,
    });

    return {
      data: {
        isFlipped: !!flipResult?.data,
      },
    };
  },
});
