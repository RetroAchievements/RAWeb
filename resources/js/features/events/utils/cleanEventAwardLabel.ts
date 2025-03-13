/**
 * Clean the event award label by removing the event title prefix if present.
 */
export function cleanEventAwardLabel(awardLabel: string, event: App.Platform.Data.Event): string {
  // Return the original label if it exactly matches the game title.
  if (awardLabel === event.legacyGame!.title) {
    return awardLabel;
  }

  return awardLabel.startsWith(event.legacyGame!.title)
    ? awardLabel.slice(event.legacyGame!.title.length).replace(/^[- :]+/, '')
    : awardLabel;
}
