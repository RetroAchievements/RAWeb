export function getNonCanonicalTitles(
  releases: App.Platform.Data.GameRelease[] | undefined,
): string[] {
  if (!releases) {
    return [];
  }

  const canonicalTitle = releases.find((r) => r.isCanonicalGameTitle)?.title;
  const canonicalTitleWithoutTags = canonicalTitle?.replace(/^~[^~]+~\s*/, '');

  return releases
    .filter((r) => !r.isCanonicalGameTitle)
    .map((r) => r.title.replace(/^~[^~]+~\s*/, ''))
    .filter((title) => title !== canonicalTitle && title !== canonicalTitleWithoutTags)
    .filter((title, index, self) => self.indexOf(title) === index)
    .sort((a, b) => a.localeCompare(b));
}
