import type { StringifiedUserPreference } from '@/common/utils/generatedAppConstants';

export type UserPreferenceValue =
  (typeof StringifiedUserPreference)[keyof typeof StringifiedUserPreference];
