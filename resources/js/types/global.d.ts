import type { Alpine } from 'alpinejs';

declare global {
    var Alpine: Alpine;
    var cfg: Record<string, unknown> | undefined;
    var clipboard: (text: string) => void;
    var showStatusSuccess: (message: string) => void;
}
