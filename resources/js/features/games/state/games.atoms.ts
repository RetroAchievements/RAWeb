import { atom } from 'jotai';

import type { PlayableListSortOrder } from '@/common/models';

import type { GameShowTab } from '../models';

export const currentListViewAtom = atom<'achievements' | 'leaderboards'>('achievements');
export const currentPlayableListSortAtom = atom<PlayableListSortOrder>('displayOrder');
export const currentTabAtom = atom<GameShowTab>('achievements');
export const isLockedOnlyFilterEnabledAtom = atom(false);
export const isMissableOnlyFilterEnabledAtom = atom(false);

/**
 * Increments on user-initiated achievement list changes to trigger animations.
 */
export const userAchievementListChangeCounterAtom = atom(0);

/**
 * This atom is detached from the dialog because we mount the dialog
 * in a different place than its trigger. At the time of writing, its
 * trigger lives in a tooltip, so when the dialog opens, the tooltip
 * unmounts. If the dialog trigger and content were siblings, this would
 * cause the dialog content to unmount too.
 */
export const isResetAllProgressDialogOpenAtom = atom(false);
