export function convertObjectToWebsitePrefs(preferences: Record<number, boolean>): number {
  let websitePrefs = 0;

  for (let i = 0; i <= 17; i++) {
    if (preferences[i]) {
      websitePrefs += 1 << i;
    }
  }

  return websitePrefs;
}
