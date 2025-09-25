import { StringifiedUserPreference } from './generatedAppConstants';

export function convertObjectToWebsitePrefs(preferences: Record<number, boolean>): number {
  let websitePrefs = 0;

  for (let i = 0; i < Object.keys(StringifiedUserPreference).length; i += 1) {
    if (preferences[i]) {
      websitePrefs += 1 << i;
    }
  }

  return websitePrefs;
}
