export type TicketCloseReason = Extract<
  App.Community.Enums.TicketAction,
  | 'already-fixed'
  | 'closed-mistaken'
  | 'closed-other'
  | 'emulator-problem'
  | 'manual-unlock-request'
  | 'network'
  | 'not-enough-info'
  | 'unable-to-debug'
  | 'unable-to-reproduce'
  | 'wrong-rom'
>;
