import type { Alpine } from 'alpinejs';

import type { expandableAwards as ExpandableAwards } from '../utils/expandableAwards';

declare global {
    var Alpine: Alpine;
    var cfg: Record<string, unknown> | undefined;
    var clipboard: (text: string) => void;
    var expandableAwards: typeof ExpandableAwards;
    var showStatusSuccess: (message: string) => void;
}
