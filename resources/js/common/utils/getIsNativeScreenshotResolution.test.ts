import { getIsNativeScreenshotResolution } from './getIsNativeScreenshotResolution';

describe('Util: getIsNativeScreenshotResolution', () => {
  const baseResolutions = [{ width: 256, height: 224 }];

  it('given an exact 1x native match, returns true', () => {
    // ASSERT
    expect(getIsNativeScreenshotResolution(256, 224, baseResolutions)).toEqual(true);
  });

  it('given a 1px tolerance match against a native resolution, returns true', () => {
    // ASSERT
    expect(getIsNativeScreenshotResolution(257, 225, baseResolutions)).toEqual(true);
  });

  it('given a 2x or 3x scaled match, returns false', () => {
    // ASSERT
    expect(getIsNativeScreenshotResolution(512, 448, baseResolutions)).toEqual(false);
    expect(getIsNativeScreenshotResolution(768, 672, baseResolutions)).toEqual(false);
  });

  it('given non-matching dimensions, returns false', () => {
    // ASSERT
    expect(getIsNativeScreenshotResolution(1920, 1080, baseResolutions)).toEqual(false);
  });

  it('given an empty native list with no analog TV output, returns false', () => {
    // ASSERT
    expect(getIsNativeScreenshotResolution(320, 240, [])).toEqual(false);
  });

  it('given an exact SMPTE 601 size, matches only when analog TV output is enabled', () => {
    // ASSERT
    expect(getIsNativeScreenshotResolution(720, 480, baseResolutions, true)).toEqual(true);
    expect(getIsNativeScreenshotResolution(720, 480, baseResolutions, false)).toEqual(false);
  });

  it('given a 1px deviation from an SMPTE 601 size with analog output enabled, returns false', () => {
    // ASSERT
    expect(getIsNativeScreenshotResolution(720, 481, baseResolutions, true)).toEqual(false);
  });

  it('given an empty native list with analog output, only exact SMPTE sizes match', () => {
    // ASSERT
    expect(getIsNativeScreenshotResolution(720, 576, [], true)).toEqual(true);
    expect(getIsNativeScreenshotResolution(1920, 1080, [], true)).toEqual(false);
  });
});
