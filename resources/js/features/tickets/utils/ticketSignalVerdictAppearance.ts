import type { IconType } from 'react-icons/lib';
import { LuBug, LuBugOff } from 'react-icons/lu';

export const TICKET_SIGNAL_VERDICT_APPEARANCE: Record<
  App.Community.Enums.TicketSignalVerdict,
  { Icon: IconType; className: string }
> = {
  'probably-not-defect': { Icon: LuBugOff, className: 'text-green-400 light:text-green-600' },
  'probably-defect': { Icon: LuBug, className: 'text-amber-400 light:text-amber-600' },
};
