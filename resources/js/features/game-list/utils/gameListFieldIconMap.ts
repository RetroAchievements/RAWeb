import { FaCalendarDay, FaTicketAlt } from 'react-icons/fa';
import { ImTrophy } from 'react-icons/im';
import { IoLogoGameControllerA } from 'react-icons/io';
import type { IconType } from 'react-icons/lib';
import { LuBox, LuCalendarPlus, LuCircleDot, LuGem, LuUser, LuWrench } from 'react-icons/lu';
import { MdOutlineLeaderboard } from 'react-icons/md';
import { PiMedalFill } from 'react-icons/pi';

// These fields don't exist on. App.Platform.Data.Game.
type ExtraKeys = 'progress' | 'retroRatio';

// Combine the Game keys and ExtraKeys.
type GameListFieldKeys = keyof App.Platform.Data.Game | ExtraKeys;

// Thanks to this util, the icon map is type-safe.
function createIconMap<T extends Partial<Record<GameListFieldKeys, IconType>>>(map: T): T {
  return map;
}

export const gameListFieldIconMap = createIconMap({
  achievementsPublished: ImTrophy,
  hasActiveOrInReviewClaims: LuWrench,
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
});
