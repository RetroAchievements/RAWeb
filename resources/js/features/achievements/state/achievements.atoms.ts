import { atom } from 'jotai';

export const currentTabAtom = atom<App.Platform.Enums.AchievementPageTab>('comments');
export const isEditModeAtom = atom(false);
export const isResetProgressDialogOpenAtom = atom(false);
export const isSavingAtom = atom(false);
export const isUpdatePromotedStatusDialogOpenAtom = atom(false);
export const quickEditSaveHandlerAtom = atom<(() => void) | null>(null);
