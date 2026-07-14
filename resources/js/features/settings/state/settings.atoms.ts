import { atom } from 'jotai';

export const settingsTabAtom = atom<App.Community.Enums.UserSettingsPageTab>('profile');
export const requestedUsernameAtom = atom<string>();
