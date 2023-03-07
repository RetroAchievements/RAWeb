import type { Alpine } from 'alpinejs';
import type { handleLeaderboardTabClick as HandleLeaderboardTabClick } from '../utils/handleLeaderboardTabClick';
import type { mobileSafeTipEvents as MobileSafeTipEvents } from '../utils/tooltip';

declare global {
    var Alpine: Alpine;
    var cfg: Record<string, unknown> | undefined;
    var copyToClipboard: (text: string) => void;
    var handleLeaderboardTabClick: typeof HandleLeaderboardTabClick;
    var clipboard: (text: string) => void;
    var mobileSafeTipEvents: typeof MobileSafeTipEvents;
    var showStatusSuccess: (message: string) => void;
    var Tip: (...args: unknown[]) => void;
}
