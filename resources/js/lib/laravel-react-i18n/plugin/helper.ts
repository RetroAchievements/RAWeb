import path from 'path';

/**
 *
 * @param rawDirname
 */
export function dirnameSanitize(rawDirname: string): string {
  return (
    rawDirname
      .replace(/\/+/g, path.sep)
      .replace(/\\+/g, path.sep)
      .replace(/[\\/]$/, '') + path.sep
  );
}
