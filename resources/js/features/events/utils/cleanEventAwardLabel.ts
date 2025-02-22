/**
 * Clean the event award label by removing the event title prefix if present.
 */
export function cleanEventAwardLabel(awardLabel: string, event: App.Platform.Data.Event): string {
  return awardLabel.startsWith(event.legacyGame!.title)
    ? awardLabel.slice(event.legacyGame!.title.length).replace(/^[- :]+/, '')
    : awardLabel;
}
