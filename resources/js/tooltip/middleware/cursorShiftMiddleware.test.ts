import { shift } from '@floating-ui/core';
import {
  type Mock,
  beforeEach,
  describe,
  it,
  expect,
  vi
} from 'vitest';

import { cursorShiftMiddleware } from './cursorShiftMiddleware';

vi.mock('@floating-ui/core', () => ({
  ...vi.importActual('@floating-ui/core'),
  shift: vi.fn().mockReturnValue({ fn: vi.fn() }),
}));

describe('Util: cursorShiftMiddleware', () => {
  beforeEach(() => {
    (shift as Mock).mockClear();
  });

  it('is defined #sanity', () => {
    expect(cursorShiftMiddleware).toBeDefined();
  });

  it('always calls the native floating-ui `shift()` function', async () => {
    // ARRANGE
    const middleware = cursorShiftMiddleware();

    // ACT
    await middleware.fn({} as any);

    // ASSERT
    expect(shift).toHaveBeenCalledTimes(1);
  });

  it('returns the `shiftResult` data to the middleware consumer', async () => {
    // ARRANGE
    (shift as Mock).mockReturnValueOnce({ fn: () => ({ x: 420, y: 69 }) });
    const middleware = cursorShiftMiddleware();

    // ACT
    const middlewareResult = await middleware.fn({} as any);

    // ASSERT
    expect(middlewareResult).toEqual({ data: { x: 420, y: 69 } });
  });
});
