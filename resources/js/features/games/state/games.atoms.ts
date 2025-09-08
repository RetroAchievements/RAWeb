import { atom } from 'jotai';

import type { PlayableListSortOrder } from '@/common/models';

export const currentPlayableListSortAtom = atom<PlayableListSortOrder>('normal');
export const currentListViewAtom = atom<'achievements' | 'leaderboards'>('achievements');
export const isLockedOnlyFilterEnabledAtom = atom(false);
export const isMissableOnlyFilterEnabledAtom = atom(false);

/**
 * This atom is detached from the dialog because we mount the dialog
 * in a different place than its trigger. At the time of writing, its
 * trigger lives in a tooltip, so when the dialog opens, the tooltip
 * unmounts. If the dialog trigger and content were siblings, this would
 * cause the dialog content to unmount too.
 */
export const isResetAllProgressDialogOpenAtom = atom(false);
