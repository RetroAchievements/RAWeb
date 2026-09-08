/**
 * "Super Mario Bros (USA) (Rev 1)" -> "(USA) (Rev 1)"
 */
export function getGameHashDisplayLabel(
  gameHash: Pick<App.Platform.Data.GameHash, 'md5' | 'name'>,
): string {
  const tags = gameHash.name?.match(/[([][^)\]]*[)\]]/g);

  if (tags?.length) {
    return tags.join(' ');
  }

  return gameHash.md5.slice(0, 8);
}
