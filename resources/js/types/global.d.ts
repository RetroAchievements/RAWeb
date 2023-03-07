import type { Alpine } from 'alpinejs';
import type { handleLeaderboardTabClick as HandleLeaderboardTabClick } from '../utils/handleLeaderboardTabClick';

declare global {
    var Alpine: Alpine;
    var cfg: Record<string, unknown> | undefined;
    var copyToClipboard: (text: string) => void;
    var handleLeaderboardTabClick: typeof HandleLeaderboardTabClick;
    var showStatusSuccess: (message: string) => void;
}
