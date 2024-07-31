import { describe, expect, it } from 'vitest';

import { getIsMobileIos } from './getIsMobileIos';

describe('Util: getIsMobileIos', () => {
  it('is defined #sanity', () => {
    expect(getIsMobileIos).toBeDefined();
  });

  it('given an iOS user agent, returns true', () => {
    const mockUserAgent =
      'Mozilla/5.0 (iPhone; CPU iPhone OS 16_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.4 Mobile/15E148 Safari/604.1';

    const isMobileIos = getIsMobileIos(mockUserAgent);

    expect(isMobileIos).toEqual(true);
  });

  it('given an Android user agent, returns false', () => {
    const mockUserAgent =
      'Mozilla/5.0 (Linux; Android 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.5615.48 Mobile Safari/537.36';

    const isMobileIos = getIsMobileIos(mockUserAgent);

    expect(isMobileIos).toEqual(false);
  });
});
