export function getNonCanonicalTitles(
  releases: App.Platform.Data.GameRelease[] | undefined,
): string[] {
  if (!releases) {
    return [];
  }

  const canonicalTitle = releases.find((r) => r.isCanonicalGameTitle)?.title;

  return releases
    .filter((r) => r.releasedAt && !r.isCanonicalGameTitle)
    .map((r) => r.title)
    .filter((title) => title !== canonicalTitle)
    .filter((title, index, self) => self.indexOf(title) === index)
    .sort((a, b) => a.localeCompare(b));
}
