import { StringifiedUserPreference } from './generatedAppConstants';

export function convertWebsitePrefsToObject(websitePrefs: number): Record<number, boolean> {
  const preferences: Record<number, boolean> = {};

  for (let i = 0; i < Object.keys(StringifiedUserPreference).length; i += 1) {
    preferences[i] = (websitePrefs & (1 << i)) !== 0;
  }

  return preferences;
}
