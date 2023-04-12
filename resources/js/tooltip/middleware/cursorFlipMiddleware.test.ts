import { flip } from '@floating-ui/dom';
import {
  type Mock,
  beforeEach,
  describe,
  it,
  expect,
  vi
} from 'vitest';

import { cursorFlipMiddleware } from './cursorFlipMiddleware';

vi.mock('@floating-ui/dom', () => ({
  ...vi.importActual('@floating-ui/dom'),
  flip: vi.fn().mockReturnValue({ fn: vi.fn() }),
}));

describe('Util: cursorFlipMiddleware', () => {
  beforeEach(() => {
    (flip as Mock).mockClear();
  });

  it('is defined #sanity', () => {
    expect(cursorFlipMiddleware).toBeDefined();
  });

  it('always calls the native floating-ui `flip()` function', async () => {
    // ARRANGE
    const middleware = cursorFlipMiddleware();

    // ACT
    await middleware.fn({} as any);

    // ASSERT
    expect(flip).toHaveBeenCalledWith({
      crossAxis: false,
      fallbackAxisSideDirection: 'start',
    });
  });

  it('returns the `flipResult` data to the middleware consumer', async () => {
    // ARRANGE
    (flip as Mock).mockReturnValueOnce({ fn: () => ({ data: true }) });
    const middleware = cursorFlipMiddleware();

    // ACT
    const middlewareResult = await middleware.fn({} as any);

    // ASSERT
    expect(middlewareResult).toEqual({ data: { isFlipped: true } });
  });
});
