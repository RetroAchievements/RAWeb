/**
 * Converts YouTube time format to seconds.
 * Handles both numeric seconds and formatted time (ie: 1h30m15s, 30m15s, 15s, 90m, etc).
 */
export function convertYouTubeTime(time: string): number {
  // If the time is numeric, it's already in seconds.
  if (!isNaN(Number(time))) {
    return parseInt(time, 10);
  }

  // Parse time in format 1h30m15s, 30m15s, 15s, 90m, etc.
  const matches = time.match(/(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?/) as RegExpMatchArray;

  const hours = matches[1] ? parseInt(matches[1], 10) : 0;
  const minutes = matches[2] ? parseInt(matches[2], 10) : 0;
  const seconds = matches[3] ? parseInt(matches[3], 10) : 0;

  return hours * 3600 + minutes * 60 + seconds;
}
