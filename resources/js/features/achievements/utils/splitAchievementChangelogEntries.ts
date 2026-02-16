type ChangelogEntry = App.Platform.Data.AchievementChangelogEntry;

interface SplitResult {
  postPromotion: ChangelogEntry[];
  prePromotion: ChangelogEntry[];
  created: ChangelogEntry | null;
  isCreatedAsPromoted: boolean;
}

/**
 * Splits changelog entries into three groups:
 * 1. postPromotion — entries from the first promotion onward (always visible).
 * 2. prePromotion — entries between creation and the first promotion (collapsible).
 * 3. created — the creation entry itself (which is always visible as the timeline anchor).
 *
 * Also determines whether the achievement was created as promoted (pre-historic V1 legacy behavior).
 */
export function splitAchievementChangelogEntries(entries: ChangelogEntry[]): SplitResult {
  // Entries are sorted descending (newest first).
  // Find the first promotion chronologically (last 'promoted' in the array).
  let firstPromotionIndex = -1;
  for (let i = entries.length - 1; i >= 0; i--) {
    if (entries[i].type === 'promoted') {
      firstPromotionIndex = i;
      break;
    }
  }

  // There's no promotion entry. If a demotion exists, the achievement was
  // born promoted (legacy core, later demoted). Otherwise it's unpublished
  // and was probably never promoted.
  if (firstPromotionIndex === -1) {
    const wasBornPromoted = entries.some((e) => e.type === 'demoted');

    return {
      postPromotion: entries,
      prePromotion: [],
      created: null,
      isCreatedAsPromoted: wasBornPromoted,
    };
  }

  const postPromotion = entries.slice(0, firstPromotionIndex + 1);
  const prePromotionAll = entries.slice(firstPromotionIndex + 1);

  // Separate the "Created" entry from the pre-promotion group.
  const created = prePromotionAll.find((e) => e.type === 'created') ?? null;
  const prePromotion = prePromotionAll.filter((e) => e.type !== 'created');

  // A demotion before the first promotion means the achievement was already
  // promoted (born promoted, demoted for repairs, re-promoted).
  const wasBornPromoted = prePromotion.some((e) => e.type === 'demoted');

  // Don't collapse if there's nothing to collapse or the achievement was born promoted.
  if (prePromotion.length === 0 || wasBornPromoted) {
    return {
      created: null,
      isCreatedAsPromoted: wasBornPromoted,
      postPromotion: entries,
      prePromotion: [],
    };
  }

  return { created, postPromotion, prePromotion, isCreatedAsPromoted: false };
}
