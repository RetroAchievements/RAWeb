import { getIsSameScreenshotResolution } from './getIsSameScreenshotResolution';

describe('Util: getIsSameScreenshotResolution', () => {
  it('given an exact match, returns true', () => {
    // ACT
    const result = getIsSameScreenshotResolution(256, 224, 256, 224);

    // ASSERT
    expect(result).toEqual(true);
  });

  it('given a 1px tolerance match, returns true', () => {
    // ACT
    const result = getIsSameScreenshotResolution(257, 225, 256, 224);

    // ASSERT
    expect(result).toEqual(true);
  });

  it('given a 2px deviation, returns false', () => {
    // ACT
    const result = getIsSameScreenshotResolution(258, 226, 256, 224);

    // ASSERT
    expect(result).toEqual(false);
  });
});
