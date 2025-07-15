import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { FaGamepad } from 'react-icons/fa';
import { ImTrophy } from 'react-icons/im';
import { PiMedalFill } from 'react-icons/pi';
import { route } from 'ziggy-js';

import { cleanHubTitle } from '@/common/utils/cleanHubTitle';

/**
 * Hub links are intentionally not using <InertiaLink />.
 * This page is particularly prone to https://github.com/inertiajs/inertia/issues/2125.
 * Once that issue is fixed in Inertia, switch to <InertiaLink />.
 */

interface SeriesHubDisplayProps {
  seriesHub: App.Platform.Data.SeriesHub;
}

export const SeriesHubDisplay: FC<SeriesHubDisplayProps> = ({ seriesHub }) => {
  const { t } = useTranslation();

  const hubHref = route('hub.show', {
    gameSet: seriesHub.hub.id,
    sort: '-playersTotal',
    'filter[subsets]': 'only-games',
  });

  return (
    <div data-testid="series-hub">
      <h2 className="mb-0 border-0 text-lg font-semibold">{t('Series')}</h2>

      <div className="rounded-lg bg-embed p-1 light:border light:border-neutral-200 light:bg-white">
        <div className="flex flex-col gap-3 rounded-lg bg-[rgba(50,50,50,0.3)] p-2">
          <div className="flex items-center gap-3">
            <a href={hubHref}>
              <img
                src={seriesHub.hub.badgeUrl!}
                alt={seriesHub.hub.title!}
                className="size-12 rounded-sm"
              />
            </a>

            <div className="flex-1">
              <a href={hubHref}>{cleanHubTitle(seriesHub.hub.title!, true)}</a>

              <div className="flex items-center gap-1.5 text-xs">
                <FaGamepad className="size-4 text-neutral-400" />
                <span>
                  <Trans
                    i18nKey="<1>{{val, number}}</1> games"
                    count={seriesHub.totalGameCount}
                    values={{ val: seriesHub.totalGameCount }}
                    components={{ 1: <span className="font-semibold" /> }}
                  />
                </span>
              </div>

              {seriesHub.totalGameCount !== seriesHub.gamesWithAchievementsCount ? (
                <p className="text-2xs">
                  {t('({{val, number}} with achievements)', {
                    val: seriesHub.gamesWithAchievementsCount,
                  })}
                </p>
              ) : null}
            </div>
          </div>

          <div className="flex w-full flex-wrap gap-x-4 gap-y-1 text-sm">
            <div className="flex items-center gap-1.5 text-xs">
              <ImTrophy className="size-4 text-neutral-400" />
              <span>
                <Trans
                  i18nKey="<1>{{val, number}}</1> achievements"
                  count={seriesHub.achievementsPublished}
                  values={{ val: seriesHub.achievementsPublished }}
                  components={{ 1: <span className="font-semibold" /> }}
                />
              </span>
            </div>

            <div className="flex items-center gap-1.5 text-xs">
              <PiMedalFill className="size-4 text-neutral-400" />
              <span>
                <Trans
                  i18nKey="<1>{{val, number}}</1> points"
                  val={seriesHub.pointsTotal}
                  values={{ val: seriesHub.pointsTotal }}
                  components={{ 1: <span className="font-semibold" /> }}
                />
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
