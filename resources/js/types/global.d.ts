import type { Alpine } from 'alpinejs';

import type { attachTooltipToElement as AttachTooltipToElement } from '@/tooltip';
import { autoExpandTextInput as AutoExpandTextInput } from '@/utils/autoExpandTextInput';
import { hideEarnedCheckboxComponent as HideEarnedCheckboxComponent } from '@/alpine/hideEarnedCheckboxComponent';
import type { newsCarousel as NewsCarousel } from '@/alpine/newsCarousel';
import type { handleLeaderboardTabClick as HandleLeaderboardTabClick } from '@/utils/handleLeaderboardTabClick';
import type { initializeTextareaCounter as InitializeTextareaCounter } from '@/utils/initializeTextareaCounter';
import type { injectShortcode as InjectShortcode } from '@/utils/injectShortcode';
import type { loadPostPreview as LoadPostPreview } from '@/utils/loadPostPreview';
import type { toggleUserCompletedSetsVisibility as ToggleUserCompletedSetsVisibility } from '@/utils/toggleUserCompletedSetsVisibility';

declare global {
  var Alpine: Alpine;
  var attachTooltipToElement: typeof AttachTooltipToElement;
  var autoExpandTextInput: typeof AutoExpandTextInput;
  var cfg: Record<string, unknown> | undefined;
  var copyToClipboard: (text: string) => void;
  var handleLeaderboardTabClick: typeof HandleLeaderboardTabClick;
  var hideEarnedCheckboxComponent: typeof HideEarnedCheckboxComponent;
  var initializeTextareaCounter: typeof InitializeTextareaCounter;
  var injectShortcode: typeof InjectShortcode;
  var loadPostPreview: typeof LoadPostPreview;
  var newsCarousel: typeof NewsCarousel;
  var showStatusSuccess: (message: string) => void;
  var toggleUserCompletedSetsVisibility: typeof ToggleUserCompletedSetsVisibility;
}
