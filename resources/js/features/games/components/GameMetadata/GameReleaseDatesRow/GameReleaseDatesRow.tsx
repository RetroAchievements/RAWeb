import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseTableCell, BaseTableRow } from '@/common/components/+vendor/BaseTable';
import { formatGameReleasedAt } from '@/common/utils/formatGameReleasedAt';

dayjs.extend(utc);

interface GameReleaseDatesRowProps {
  releases: App.Platform.Data.GameRelease[];
}

export const GameReleaseDatesRow: FC<GameReleaseDatesRowProps> = ({ releases }) => {
  const { t } = useTranslation();

  // First, deduplicate by region, keeping only the earliest release per region.
  const dedupedReleases = deduplicateReleasesByRegion(releases);

  // Then, sort by date.
  const sortedReleases = sortReleasesByDate(dedupedReleases);

  return (
    <BaseTableRow className="first:rounded-t-lg last:rounded-b-lg">
      <BaseTableCell className="text-right align-top">
        {t('metaRelease', { count: sortedReleases.length })}
      </BaseTableCell>

      <BaseTableCell>
        <div className="flex flex-col">
          {sortedReleases.map((release) => {
            // Treat "other" and null as "Worldwide" for now.
            const displayRegion =
              !release.region || release.region === 'other' || release.region === 'worldwide'
                ? 'WW'
                : release.region;

            // Hide the region if there's only one release and it's worldwide.
            const shouldShowRegion = !(sortedReleases.length === 1 && displayRegion === 'WW');

            return (
              <span key={release.id}>
                {shouldShowRegion ? (
                  <span className="mr-1.5 font-mono uppercase">{displayRegion}</span>
                ) : null}
                {formatGameReleasedAt(release.releasedAt, release.releasedAtGranularity)}
              </span>
            );
          })}
        </div>
      </BaseTableCell>
    </BaseTableRow>
  );
};

function deduplicateReleasesByRegion(
  releases: App.Platform.Data.GameRelease[],
): App.Platform.Data.GameRelease[] {
  const regionMap = new Map<string, App.Platform.Data.GameRelease>();

  for (const release of releases) {
    // Normalize the region for comparison.
    const normalizedRegion =
      !release.region || release.region === 'other' || release.region === 'worldwide'
        ? 'worldwide'
        : release.region;

    const existing = regionMap.get(normalizedRegion);

    // If no existing release for this region, or this release is earlier, use it.
    if (
      !existing ||
      (release.releasedAt && (!existing.releasedAt || isEarlierRelease(release, existing)))
    ) {
      regionMap.set(normalizedRegion, release);
    }
  }

  return Array.from(regionMap.values());
}

function isEarlierRelease(
  a: App.Platform.Data.GameRelease,
  b: App.Platform.Data.GameRelease,
): boolean {
  const dateA = dayjs.utc(a.releasedAt);
  const dateB = dayjs.utc(b.releasedAt);

  const normalizedDateA = normalizeDate(dateA, a.releasedAtGranularity);
  const normalizedDateB = normalizeDate(dateB, b.releasedAtGranularity);

  return normalizedDateA.isBefore(normalizedDateB);
}

function sortReleasesByDate(
  releases: App.Platform.Data.GameRelease[],
): App.Platform.Data.GameRelease[] {
  return [...releases].sort((a, b) => {
    const dateA = dayjs.utc(a.releasedAt);
    const dateB = dayjs.utc(b.releasedAt);

    // First, normalize dates based on granularity.
    const normalizedDateA = normalizeDate(dateA, a.releasedAtGranularity);
    const normalizedDateB = normalizeDate(dateB, b.releasedAtGranularity);

    // Then, compare normalized dates.
    const dateDiff = normalizedDateA.valueOf() - normalizedDateB.valueOf();

    if (dateDiff !== 0) {
      return dateDiff;
    }

    // If the dates are equal, sort by granularity (more specific first).
    const granularityOrder = { day: 3, month: 2, year: 1 };
    const granularityA = a.releasedAtGranularity ? granularityOrder[a.releasedAtGranularity] : 0;
    const granularityB = b.releasedAtGranularity ? granularityOrder[b.releasedAtGranularity] : 0;

    return granularityB - granularityA;
  });
}

function normalizeDate(date: dayjs.Dayjs, granularity: string | null): dayjs.Dayjs {
  switch (granularity) {
    case 'year':
      return date.utc().startOf('year');

    case 'month':
      return date.utc().startOf('month');

    case 'day':
    default:
      return date;
  }
}
