import { useMemo } from 'react';

export function useDeduplicatedReleases(
  releases: App.Platform.Data.GameRelease[],
): App.Platform.Data.GameRelease[] {
  return useMemo(() => {
    const seen = new Set<string>();

    return releases.filter((release) => {
      const region =
        !release.region || release.region === 'other' || release.region === 'worldwide'
          ? 'WW'
          : release.region;
      const date = release.releasedAt?.split('T')[0] ?? 'no-date';
      const key = `${region}_${date}`;

      if (seen.has(key)) {
        return false;
      }
      seen.add(key);

      return true;
    });
  }, [releases]);
}
