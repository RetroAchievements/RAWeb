const SMPTE_601_RESOLUTIONS = [
  { width: 704, height: 480 },
  { width: 720, height: 480 },
  { width: 720, height: 486 },
  { width: 704, height: 576 },
  { width: 720, height: 576 },
];

export function isValidScreenshotResolution(
  width: number,
  height: number,
  allValidResolutions: Array<{ width: number; height: number }>,
  hasAnalogTvOutput?: boolean,
  supportsUpscaledScreenshots?: boolean,
): boolean {
  // When no resolutions are defined, any given resolution is acceptable.
  if (allValidResolutions.length === 0) {
    return true;
  }

  const tolerancePx = 1;
  const maxScale = supportsUpscaledScreenshots ? 3 : 1;

  for (const resolution of allValidResolutions) {
    for (let scale = 1; scale <= maxScale; scale++) {
      const expectedW = resolution.width * scale;
      const expectedH = resolution.height * scale;

      if (
        Math.abs(width - expectedW) <= tolerancePx &&
        Math.abs(height - expectedH) <= tolerancePx
      ) {
        return true;
      }
    }
  }

  // SMPTE 601 analog capture resolutions are an exact-match check.
  if (hasAnalogTvOutput) {
    for (const smpte of SMPTE_601_RESOLUTIONS) {
      if (width === smpte.width && height === smpte.height) {
        return true;
      }
    }
  }

  return false;
}
