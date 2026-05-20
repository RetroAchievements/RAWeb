import { getIsSameScreenshotResolution } from './getIsSameScreenshotResolution';
import { SCREENSHOT_SMPTE_RESOLUTIONS } from './screenshotSmpteResolutions';

/**
 * Returns true only when the dimensions are a 1x native capture, never a 2x/3x upscale.
 *
 * Diverges from getIsValidScreenshotResolution() in two important ways:
 * - An empty native list returns false (rather than "any size is valid"). This prevents
 *   every upload on a system with no resolution metadata from being treated as native.
 * - 2x/3x multiples never match here, even when the system supports upscaling.
 *
 * SMPTE 601 sizes count as 1x when hasAnalogTvOutput is true, since they're real hardware
 * captures (not upscales), and use exact equality (no tolerance), matching the validator.
 */
export function getIsNativeScreenshotResolution(
  width: number,
  height: number,
  nativeResolutions: Array<{ width: number; height: number }>,
  hasAnalogTvOutput?: boolean,
): boolean {
  for (const resolution of nativeResolutions) {
    if (getIsSameScreenshotResolution(width, height, resolution.width, resolution.height)) {
      return true;
    }
  }

  if (hasAnalogTvOutput) {
    for (const smpte of SCREENSHOT_SMPTE_RESOLUTIONS) {
      if (width === smpte.width && height === smpte.height) {
        return true;
      }
    }
  }

  return false;
}
