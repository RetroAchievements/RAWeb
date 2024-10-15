import { FaCalendarDay, FaTicketAlt } from 'react-icons/fa';
import { ImTrophy } from 'react-icons/im';
import { IoLogoGameControllerA } from 'react-icons/io';
import type { IconType } from 'react-icons/lib';
import { LuBox, LuCalendarPlus, LuCircleDot, LuGem, LuUser } from 'react-icons/lu';
import { MdOutlineLeaderboard } from 'react-icons/md';
import { PiMedalFill } from 'react-icons/pi';

export const gameListFieldIconMap: Partial<
  Record<keyof App.Platform.Data.Game | 'progress' | 'retroRatio', IconType>
> = {
  achievementsPublished: ImTrophy,
  lastUpdated: LuCalendarPlus,
  numUnresolvedTickets: FaTicketAlt,
  numVisibleLeaderboards: MdOutlineLeaderboard,
  playersTotal: LuUser,
  pointsTotal: PiMedalFill,
  progress: LuCircleDot,
  releasedAt: FaCalendarDay,
  retroRatio: LuGem,
  system: LuBox,
  title: IoLogoGameControllerA,
};
