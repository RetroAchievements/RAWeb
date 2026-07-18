import type { CSSProperties } from 'react';

const CRISP_EDGES_MAX_SOURCE_WIDTH = 640;

/**
 * Keep small-system screenshots like DS/PS1 sharp when they are enlarged,
 * while leaving HD-era captures like Wii to the browser's default smoothing.
 * The width cutoff is an intentionally simple proxy for that split.
 */
export function getScreenshotImageRendering(
  sourceWidth: number | null | undefined,
  isPixelated?: boolean,
): CSSProperties['imageRendering'] {
  if (isPixelated) {
    return 'pixelated';
  }

  if (sourceWidth === null || sourceWidth === undefined || sourceWidth <= 0) {
    return undefined;
  }

  if (sourceWidth <= CRISP_EDGES_MAX_SOURCE_WIDTH) {
    return 'crisp-edges';
  }

  return undefined;
}
