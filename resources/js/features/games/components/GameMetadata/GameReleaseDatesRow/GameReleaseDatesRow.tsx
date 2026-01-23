import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseTableCell, BaseTableHead, BaseTableRow } from '@/common/components/+vendor/BaseTable';
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { useFormatGameReleasedAt } from '@/common/hooks/useFormatGameReleasedAt';
import { useDeduplicatedReleases } from '@/features/games/hooks/useDeduplicatedReleases';

interface GameReleaseDatesRowProps {
  releases: App.Platform.Data.GameRelease[];
}

export const GameReleaseDatesRow: FC<GameReleaseDatesRowProps> = ({ releases }) => {
  const { t } = useTranslation();
  const { formatGameReleasedAt } = useFormatGameReleasedAt();

  const uniqueReleases = useDeduplicatedReleases(releases);

  return (
    <BaseTableRow className="first:rounded-t-lg last:rounded-b-lg">
      <BaseTableHead scope="row" className="h-auto text-right align-top text-text">
        {t('metaRelease', { count: uniqueReleases.length })}
      </BaseTableHead>

      <BaseTableCell>
        <span className="flex flex-col">
          {uniqueReleases.map((release) => {
            // Treat "other" and null as "Worldwide" for now.
            const displayRegion =
              !release.region || release.region === 'other' || release.region === 'worldwide'
                ? 'WW'
                : release.region;

            // Hide the region if there's only one release and it's worldwide.
            const shouldShowRegion = !(uniqueReleases.length === 1 && displayRegion === 'WW');

            return (
              <span key={release.id}>
                {shouldShowRegion ? (
                  <BaseTooltip>
                    <BaseTooltipTrigger asChild>
                      <span className="mr-1.5 font-mono uppercase">{displayRegion}</span>
                    </BaseTooltipTrigger>

                    <BaseTooltipContent>
                      {/* eslint-disable-next-line @typescript-eslint/no-explicit-any -- the key is dynamic */}
                      {t(`region_${displayRegion}`.toLowerCase() as any)}
                    </BaseTooltipContent>
                  </BaseTooltip>
                ) : null}
                {formatGameReleasedAt(release.releasedAt, release.releasedAtGranularity)}
              </span>
            );
          })}
        </span>
      </BaseTableCell>
    </BaseTableRow>
  );
};
