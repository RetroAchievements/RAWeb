const arcadeTitleRegex = /<([^>]*)>/; // ie: "avsp.7z <Alien vs Predator (940520 Euro)>"
const leadingTagsRegex = /^(?:\s*[([][^)\]]*[)\]])+/;
const fileExtensionRegex = /\.(?=[a-z\d]*[a-z])[a-z\d]{1,4}(?=\s*(?:[([]|$))/gi;

/**
 * "Super Mario Bros (USA) (Rev 1).nes" -> "(USA) (Rev 1)"
 * "The Black Cauldron (4am crack) disk 1A.dsk" -> "(4am crack) disk 1A"
 * "[MSU-1] Chrono Trigger (USA) (v1.1).sfc" -> "[MSU-1] (USA) (v1.1)"
 */
export function getGameHashDisplayLabel(
  gameHash: Pick<App.Platform.Data.GameHash, 'md5' | 'name'>,
): string {
  const originalName = gameHash.name ?? '';
  const cleanName = (originalName.match(arcadeTitleRegex)?.[1] ?? originalName)
    .replace(fileExtensionRegex, '')
    .replace(/([)\]])(?=[([])/g, '$1 ')
    .replace(/\s+/g, ' ')
    .trim();

  const openingTagPrefix = cleanName.match(leadingTagsRegex)?.[0] ?? '';
  const unprefixedName = cleanName.slice(openingTagPrefix.length);

  const firstBracketOffset = unprefixedName.search(/[([]/);
  const bracketedTail = firstBracketOffset === -1 ? '' : unprefixedName.slice(firstBracketOffset);

  const displayLabel = [openingTagPrefix.trim(), bracketedTail.trim()].filter(Boolean).join(' ');

  return displayLabel || gameHash.md5.slice(0, 8);
}
