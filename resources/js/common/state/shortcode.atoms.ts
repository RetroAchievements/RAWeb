import { atom } from 'jotai';

export const persistedAchievementsAtom = atom<App.Platform.Data.Achievement[]>();
export const persistedGamesAtom = atom<App.Platform.Data.Game[]>();
export const persistedHubsAtom = atom<App.Platform.Data.GameSet[]>();
export const persistedEventsAtom = atom<App.Platform.Data.Event[]>();
export const persistedTicketsAtom = atom<App.Platform.Data.Ticket[]>();
export const persistedUsersAtom = atom<App.Data.User[]>();
