import { atom } from 'jotai';

import type { AchievementSortOrder } from '@/common/models';

export const currentAchievementSortAtom = atom<AchievementSortOrder>('normal');
export const currentListViewAtom = atom<'achievements' | 'leaderboards'>('achievements');
export const isLockedOnlyFilterEnabledAtom = atom(false);
export const isMissableOnlyFilterEnabledAtom = atom(false);
