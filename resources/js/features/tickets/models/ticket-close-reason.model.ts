export type TicketCloseReason = Extract<
  App.Community.Enums.TicketAction,
  | 'already-fixed'
  | 'belongs-elsewhere'
  | 'closed-mistaken'
  | 'closed-other'
  | 'emulator-problem'
  | 'network'
  | 'not-enough-info'
  | 'unable-to-debug'
  | 'unable-to-reproduce'
  | 'wrong-rom'
>;
