export function convertWebsitePrefsToObject(websitePrefs: number): Record<number, boolean> {
  const preferences: Record<number, boolean> = {};

  for (let i = 0; i <= 17; i++) {
    preferences[i] = (websitePrefs & (1 << i)) !== 0;
  }

  return preferences;
}
