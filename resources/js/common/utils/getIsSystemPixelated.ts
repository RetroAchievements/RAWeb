/**
 * Systems with 3D-capable hardware produce screenshots that look worse
 * with nearest-neighbor upscaling. For these systems, we skip the
 * pixelated image-rendering filter.
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
  42, // Philips CD-i
  43, // 3DO Interactive Multiplayer
  62, // Nintendo 3DS
  70, // Zeebo
  78, // Nintendo DSi
]);

export function getIsSystemPixelated(systemId: number): boolean {
  return !nonPixelatedSystemIds.has(systemId);
}
