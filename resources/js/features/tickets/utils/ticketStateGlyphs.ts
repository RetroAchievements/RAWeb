import type { IconType } from 'react-icons';
import { LuCircle, LuCircleCheck, LuCircleDot, LuCircleSlash, LuCircleX } from 'react-icons/lu';

/**
 * Each state has a designated shape and color. This is a deliberate a11y choice to
 * ensure the glyphs remain distinct for the ~10% of users who are colorblind.
 */
export const TICKET_STATE_GLYPHS: Record<
  App.Community.Enums.TicketState,
  { Icon: IconType; className: string }
> = {
  open: { Icon: LuCircle, className: 'text-neutral-400 light:text-neutral-600' },
  request: { Icon: LuCircleDot, className: 'text-sky-300' },
  resolved: { Icon: LuCircleCheck, className: 'text-green-500' },
  closed: { Icon: LuCircleX, className: 'text-neutral-500' },
  quarantined: { Icon: LuCircleSlash, className: 'text-rose-500' },
};
