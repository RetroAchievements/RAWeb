import { atom } from 'jotai';

import type { EmulatorSortOrder } from '../models';

export const isAllSystemsDialogOpenAtom = atom<boolean>(false);

export const searchQueryAtom = atom('');
export const selectedPlatformIdAtom = atom<number | null>();
export const selectedSystemIdAtom = atom<number | null>();
export const sortByAtom = atom<EmulatorSortOrder>('popularity');
