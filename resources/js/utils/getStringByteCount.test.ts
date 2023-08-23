import { describe, it, expect } from 'vitest';

import { getStringByteCount } from './getStringByteCount';

describe('Util: getStringByteCount', () => {
  it('is defined #sanity', () => {
    expect(getStringByteCount).toBeDefined();
  });

  it('given a string, correctly calculates the number of bytes', () => {
    const mockValue = '123456789 123456789 123456789 123456789 123456789½';

    const stringByteCount = getStringByteCount(mockValue);

    expect(stringByteCount).toEqual(51);
  });
});
