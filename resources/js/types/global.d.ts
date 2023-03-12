import type { Alpine } from 'alpinejs';
import type { mobileSafeTipEvents as MobileSafeTipEvents } from '../utils/tooltip';
import type { attachTooltipToElement as AttachTooltipToElement } from '../tooltip';

declare global {
    var Alpine: Alpine;
    var attachTooltipToElement: typeof AttachTooltipToElement;
    var cfg: Record<string, unknown> | undefined;
    var clipboard: (text: string) => void;
    var mobileSafeTipEvents: typeof MobileSafeTipEvents;
    var showStatusSuccess: (message: string) => void;
    var Tip: (...args: unknown[]) => void;
}
