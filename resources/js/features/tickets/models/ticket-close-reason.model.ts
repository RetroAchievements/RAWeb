export type TicketCloseReason = Extract<
  App.Community.Enums.TicketAction,
  | 'closed-mistaken'
  | 'closed-other'
  | 'network'
  | 'not-enough-info'
  | 'unable-to-debug'
  | 'unable-to-reproduce'
  | 'wrong-rom'
>;
