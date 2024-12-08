/**
 * At the time of writing, hub titles are surrounded by square brackets.
 * This enables a lot of UI workarounds that we should mitigate in the future.
 * For now, these square brackets are generally undesirable in certain contexts,
 * so we can use this util to strip them.
 */
export function cleanHubTitle(originalTitle: string): string {
  const trimmed = originalTitle.trim();
  if (trimmed.startsWith('[') && trimmed.endsWith(']')) {
    return trimmed.slice(1, -1);
  }

  return originalTitle;
}
