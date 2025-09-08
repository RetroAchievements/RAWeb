import { atom } from 'jotai';

import type { PlayableListSortOrder } from '@/common/models';

export const currentPlayableListSortAtom = atom<PlayableListSortOrder>('normal');
export const currentListViewAtom = atom<'achievements' | 'leaderboards'>('achievements');
export const isLockedOnlyFilterEnabledAtom = atom(false);
export const isMissableOnlyFilterEnabledAtom = atom(false);
