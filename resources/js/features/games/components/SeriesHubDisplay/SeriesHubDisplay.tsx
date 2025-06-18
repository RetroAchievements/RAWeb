import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { FaGamepad } from 'react-icons/fa';
import { ImTrophy } from 'react-icons/im';
import { PiMedalFill } from 'react-icons/pi';
import { route } from 'ziggy-js';

import { GameAvatar } from '@/common/components/GameAvatar';
import { cleanHubTitle } from '@/common/utils/cleanHubTitle';

/**
 * Hub links are intentionally not using <InertiaLink />.
 * This page is particularly prone to https://github.com/inertiajs/inertia/issues/2125.
 * Once that issue is fixed in Inertia, switch to <InertiaLink />.
 */

interface SeriesHubDisplayProps {
  game: App.Platform.Data.Game;
  seriesHub: App.Platform.Data.SeriesHub;
}

export const SeriesHubDisplay: FC<SeriesHubDisplayProps> = ({ game, seriesHub }) => {
  const { t } = useTranslation();

  return (
    <div data-testid="series-hub">
      <h2 className="mb-0 border-0 text-lg font-semibold">{t('Series')}</h2>

      <div className="rounded-lg bg-embed p-1 light:border light:border-neutral-200 light:bg-white">
        <div className="flex flex-col gap-3 rounded-lg bg-[rgba(50,50,50,0.3)] p-2">
          <div className="flex items-center gap-3">
            <img
              src={seriesHub.hub.badgeUrl!}
              alt={seriesHub.hub.title!}
              className="size-12 rounded-sm"
            />

            <div className="flex-1">
              <a href={route('hub.show', { gameSet: seriesHub.hub.id })}>
                {cleanHubTitle(seriesHub.hub.title!, true)}
              </a>

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
            </div>
          </div>

          {seriesHub.topGames.length >= 2 ? (
            <div className="flex gap-1 overflow-hidden">
              <div
                data-testid={`0-${seriesHub.topGames[0].id === game.id ? 'highlight' : 'no-highlight'}`}
                className={
                  seriesHub.topGames[0].id === game.id ? 'border-b-2 border-text pb-1' : undefined
                }
              >
                <GameAvatar size={40} showLabel={false} {...seriesHub.topGames[0]} />
              </div>
              <div
                data-testid={`1-${seriesHub.topGames[1].id === game.id ? 'highlight' : 'no-highlight'}`}
                className={
                  seriesHub.topGames[1].id === game.id ? 'border-b-2 border-text pb-1' : undefined
                }
              >
                <GameAvatar size={40} showLabel={false} {...seriesHub.topGames[1]} />
              </div>

              {seriesHub.topGames?.[2] ? (
                <div
                  data-testid={`2-${seriesHub.topGames[2].id === game.id ? 'highlight' : 'no-highlight'}`}
                  className={
                    seriesHub.topGames[2].id === game.id ? 'border-b-2 border-text pb-1' : undefined
                  }
                >
                  <GameAvatar size={40} showLabel={false} {...seriesHub.topGames[2]} />
                </div>
              ) : null}

              {seriesHub.topGames?.[3] ? (
                <div
                  data-testid={`3-${seriesHub.topGames[3].id === game.id ? 'highlight' : 'no-highlight'}`}
                  className={
                    seriesHub.topGames[3].id === game.id ? 'border-b-2 border-text pb-1' : undefined
                  }
                >
                  <GameAvatar size={40} showLabel={false} {...seriesHub.topGames[3]} />
                </div>
              ) : null}

              {seriesHub.topGames?.[4] ? (
                <div
                  data-testid={`4-${seriesHub.topGames[4].id === game.id ? 'highlight' : 'no-highlight'}`}
                  className={
                    seriesHub.topGames[4].id === game.id ? 'border-b-2 border-text pb-1' : undefined
                  }
                >
                  <GameAvatar size={40} showLabel={false} {...seriesHub.topGames[4]} />
                </div>
              ) : null}

              {seriesHub.additionalGameCount > 0 ? (
                <a
                  href={route('hub.show', { gameSet: seriesHub.hub.id })}
                  className="flex size-10 flex-shrink-0 items-center justify-center rounded-sm bg-neutral-700/50 text-2xs text-neutral-400"
                >
                  {t('+{{val, number}}', { val: seriesHub.additionalGameCount })}
                </a>
              ) : null}
            </div>
          ) : null}

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
