import type { Alpine } from 'alpinejs';
import type { attachTooltipToElement as AttachTooltipToElement } from '../tooltip';

declare global {
    var Alpine: Alpine;
    var attachTooltipToElement: typeof AttachTooltipToElement;
    var cfg: Record<string, unknown> | undefined;
    var clipboard: (text: string) => void;
    var showStatusSuccess: (message: string) => void;
}
