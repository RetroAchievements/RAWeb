import { atom } from 'jotai';

export const currentTabAtom = atom<App.Platform.Enums.AchievementPageTab>('comments');

export const isResetProgressDialogOpenAtom = atom(false);
