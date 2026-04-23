const SCREENSHOT_RESOLUTION_TOLERANCE_PX = 1;

export function getIsSameScreenshotResolution(
  actualWidth: number,
  actualHeight: number,
  expectedWidth: number,
  expectedHeight: number,
): boolean {
  return (
    Math.abs(actualWidth - expectedWidth) <= SCREENSHOT_RESOLUTION_TOLERANCE_PX &&
    Math.abs(actualHeight - expectedHeight) <= SCREENSHOT_RESOLUTION_TOLERANCE_PX
  );
}
