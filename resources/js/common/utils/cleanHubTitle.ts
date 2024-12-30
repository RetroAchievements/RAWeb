/**
 * At the time of writing, hub titles are surrounded by square brackets.
 * This enables a lot of UI workarounds that we should mitigate in the future.
 * For now, these square brackets are generally undesirable in certain contexts,
 * so we can use this util to strip them.
 *
 * @param originalTitle The original hub title which may be wrapped by square brackets ("[Central - Series]").
 * @param shouldRemovePrefix When true, removes "Foo - " style prefixes from the title.
 */
export function cleanHubTitle(originalTitle: string, shouldRemovePrefix?: boolean): string {
  const trimmed = originalTitle.trim();
  const withoutBrackets =
    trimmed.startsWith('[') && trimmed.endsWith(']') ? trimmed.slice(1, -1) : originalTitle;

  // "Central - Series" -> "Series"
  if (shouldRemovePrefix) {
    const parts = withoutBrackets.split(' - ');

    return parts.length > 1 ? parts.slice(1).join(' - ') : withoutBrackets;
  }

  return withoutBrackets;
}
