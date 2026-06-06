type ChangelogEntry = App.Platform.Data.AchievementChangelogEntry;

interface SplitOptions {
  isPromoted?: boolean;
}

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
export function splitAchievementChangelogEntries(
  entries: ChangelogEntry[],
  options: SplitOptions,
): SplitResult {
  // Entries are sorted descending (newest first).
  // Find the first promotion chronologically (last 'promoted' in the array).
  let firstPromotionIndex = -1;
  for (let i = entries.length - 1; i >= 0; i--) {
    if (entries[i].type === 'promoted') {
      firstPromotionIndex = i;
      break;
    }
  }

  // There's no promotion entry. The achievement was created-as-promoted if it
  // has a demotion (legacy core, later demoted) or if it's currently
  // promoted with no promotion record at all (pre-historic V1 achievements).
  if (firstPromotionIndex === -1) {
    const wasCreatedAsPromoted =
      entries.some((e) => e.type === 'demoted') || options.isPromoted === true;

    return {
      postPromotion: entries,
      prePromotion: [],
      created: null,
      isCreatedAsPromoted: wasCreatedAsPromoted,
    };
  }

  const postPromotion = entries.slice(0, firstPromotionIndex + 1);
  const prePromotionAll = entries.slice(firstPromotionIndex + 1);

  // Separate the "Created" entry from the pre-promotion group.
  const created = prePromotionAll.find((e) => e.type === 'created') ?? null;
  const prePromotion = prePromotionAll.filter((e) => e.type !== 'created');

  // A demotion before the first promotion means the achievement was already
  // promoted (created-as-promoted, demoted for repairs, re-promoted).
  const wasCreatedAsPromoted = prePromotion.some((e) => e.type === 'demoted');

  // Don't collapse if there's nothing to collapse or the achievement was created-as-promoted.
  if (prePromotion.length === 0 || wasCreatedAsPromoted) {
    return {
      created: null,
      isCreatedAsPromoted: wasCreatedAsPromoted,
      postPromotion: entries,
      prePromotion: [],
    };
  }

  return { created, postPromotion, prePromotion, isCreatedAsPromoted: false };
}
