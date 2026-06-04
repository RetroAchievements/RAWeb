/**
 * SMPTE 601 analog-capture resolutions, used by systems with `has_analog_tv_output`.
 * Matched with no tolerance. These are well-defined standards.
 */
export const SCREENSHOT_SMPTE_RESOLUTIONS = [
  { width: 704, height: 480 },
  { width: 720, height: 480 },
  { width: 720, height: 486 },
  { width: 704, height: 576 },
  { width: 720, height: 576 },
] as const;
