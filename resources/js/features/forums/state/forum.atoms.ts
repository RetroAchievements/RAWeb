import { atom } from 'jotai';

export const persistedUsersAtom = atom<App.Data.User[]>();

export const persistedGamesAtom = atom<App.Platform.Data.Game[]>();

export const persistedAchievementsAtom = atom<App.Platform.Data.Achievement[]>();

export const persistedTicketsAtom = atom<App.Platform.Data.Ticket[]>();
