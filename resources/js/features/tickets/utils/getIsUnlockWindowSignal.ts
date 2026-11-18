export function getIsUnlockWindowSignal(kind: App.Community.Enums.TicketSignal): boolean {
  return kind === 'many-recent-unlocks' || kind === 'no-recent-unlocks';
}
