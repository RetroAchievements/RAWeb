import { getIsValidScreenshotResolution } from './getIsValidScreenshotResolution';

describe('Util: getIsValidScreenshotResolution', () => {
  const baseResolutions = [{ width: 256, height: 224 }];

  it('given an exact 1x match, returns true', () => {
    // ACT
    const result = getIsValidScreenshotResolution(256, 224, baseResolutions);

    // ASSERT
    expect(result).toEqual(true);
  });

  it('given a 2x scaled match with upscaling supported, returns true', () => {
    // ACT
    const result = getIsValidScreenshotResolution(512, 448, baseResolutions, false, true);

    // ASSERT
    expect(result).toEqual(true);
  });

  it('given a 3x scaled match with upscaling supported, returns true', () => {
    // ACT
    const result = getIsValidScreenshotResolution(768, 672, baseResolutions, false, true);

    // ASSERT
    expect(result).toEqual(true);
  });

  it('given a 2x scaled match without upscaling supported, returns false', () => {
    // ACT
    const result = getIsValidScreenshotResolution(512, 448, baseResolutions, false, false);

    // ASSERT
    expect(result).toEqual(false);
  });

  it('given a 1px tolerance match, returns true', () => {
    // ACT
    const result = getIsValidScreenshotResolution(257, 225, baseResolutions);

    // ASSERT
    expect(result).toEqual(true);
  });

  it('given a 2px deviation, returns false', () => {
    // ACT
    const result = getIsValidScreenshotResolution(258, 226, baseResolutions);

    // ASSERT
    expect(result).toEqual(false);
  });

  it('given an empty resolutions array, returns true', () => {
    // ACT
    const result = getIsValidScreenshotResolution(999, 999, []);

    // ASSERT
    expect(result).toEqual(true);
  });

  it('given a totally invalid resolution, returns false', () => {
    // ACT
    const result = getIsValidScreenshotResolution(1920, 1080, baseResolutions);

    // ASSERT
    expect(result).toEqual(false);
  });

  it('given an SMPTE 601 resolution with analog output enabled, returns true', () => {
    // ACT
    const result = getIsValidScreenshotResolution(720, 480, baseResolutions, true);

    // ASSERT
    expect(result).toEqual(true);
  });

  it('given an SMPTE 601 resolution without analog output, returns false', () => {
    // ACT
    const result = getIsValidScreenshotResolution(720, 480, baseResolutions, false);

    // ASSERT
    expect(result).toEqual(false);
  });

  it('given a valid SMPTE 601 PAL resolution, returns true', () => {
    // ACT
    const result = getIsValidScreenshotResolution(720, 576, baseResolutions, true);

    // ASSERT
    expect(result).toEqual(true);
  });
});
