import { atom } from 'jotai';

import type { AchievementShowTab } from '../models';

export const currentTabAtom = atom<AchievementShowTab>('comments');
