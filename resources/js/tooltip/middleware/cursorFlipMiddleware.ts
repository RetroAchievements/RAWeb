import { flip, type Middleware } from '@floating-ui/dom';

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
