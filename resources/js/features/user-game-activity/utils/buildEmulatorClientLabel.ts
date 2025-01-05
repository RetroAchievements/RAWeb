/**
 * Builds a formatted emulator client label string from parsed user agent data.
 *
 * Format: "{client} {version} [- {variation}] [({os})]"
 * where bracketed sections are optional.
 *
 * @example
 * // Basic client and version.
 * buildClientLabel({ client: 'RALibRetro', clientVersion: '1.19.1' })
 * // Returns: "RALibRetro 1.19.1".
 *
 * @example
 * // With a variation.
 * buildClientLabel({
 *   client: 'RALibRetro',
 *   clientVersion: '1.19.1',
 *   clientVariation: 'mesen'
 * })
 * // Returns: "RALibRetro 1.19.1 - mesen".
 *
 * @example
 * // Complete with OS
 * buildClientLabel({
 *   client: 'RALibRetro',
 *   clientVersion: '1.19.1',
 *   clientVariation: 'mesen',
 *   os: 'Windows 8 x64 Build 9200 6.2'
 * })
 * // Returns: "RALibRetro 1.19.1 - mesen (Windows 8 x64 Build 9200 6.2)".
 */
export function buildEmulatorClientLabel(
  parsedUserAgent?: App.Platform.Data.ParsedUserAgent,
): string {
  if (!parsedUserAgent) {
    return '';
  }

  const basePart = `${parsedUserAgent.client} ${parsedUserAgent.clientVersion}`;
  const variationPart = parsedUserAgent.clientVariation
    ? ` - ${parsedUserAgent.clientVariation}`
    : '';
  const osPart = parsedUserAgent.os ? ` (${parsedUserAgent.os})` : '';

  return `${basePart}${variationPart}${osPart}`;
}
