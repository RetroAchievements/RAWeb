import type { Alpine } from 'alpinejs';

import { hideEarnedCheckboxComponent as HideEarnedCheckboxComponent } from '@/alpine/hideEarnedCheckboxComponent';
import type { handleLeaderboardTabClick as HandleLeaderboardTabClick } from '@/utils/handleLeaderboardTabClick';
import type { injectShortcode as InjectShortcode } from '@/utils/injectShortcode';
import type { mobileSafeTipEvents as MobileSafeTipEvents } from '@/utils/tooltip';
import type { toggleUserCompletedSetsVisibility as ToggleUserCompletedSetsVisibility } from '@/utils/toggleUserCompletedSetsVisibility';

declare global {
  var Alpine: Alpine;
  var cfg: Record<string, unknown> | undefined;
  var copyToClipboard: (text: string) => void;
  var handleLeaderboardTabClick: typeof HandleLeaderboardTabClick;
  var hideEarnedCheckboxComponent: typeof HideEarnedCheckboxComponent;
  var injectShortcode: typeof InjectShortcode;
  var mobileSafeTipEvents: typeof MobileSafeTipEvents;
  var showStatusSuccess: (message: string) => void;
  var Tip: (...args: unknown[]) => void;
  var toggleUserCompletedSetsVisibility: typeof ToggleUserCompletedSetsVisibility;
}
