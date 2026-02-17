/**
 * Systems with 3D-capable hardware where emulators commonly upscale
 * beyond native resolution. Nearest-neighbor upscaling makes these
 * screenshots look worse, so we skip the pixelated filter for them.
 */
const nonPixelatedSystemIds = new Set([
  2, // Nintendo 64
  12, // PlayStation
  16, // GameCube
  18, // Nintendo DS
  19, // Wii
  20, // Wii U
  21, // PlayStation 2
  22, // Xbox
  39, // Saturn
  40, // Dreamcast
  41, // PlayStation Portable
  62, // Nintendo 3DS
  78, // Nintendo DSi
]);

export function getIsSystemPixelated(systemId: number): boolean {
  return !nonPixelatedSystemIds.has(systemId);
}
