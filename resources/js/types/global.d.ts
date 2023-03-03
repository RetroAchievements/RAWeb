import type { Alpine } from 'alpinejs';

import type { badgeGroup as BadgeGroup } from '../utils/badgeGroup';

declare global {
    var Alpine: Alpine;
    var badgeGroup: typeof BadgeGroup;
    var cfg: Record<string, unknown> | undefined;
    var clipboard: (text: string) => void;
    var showStatusSuccess: (message: string) => void;
}
