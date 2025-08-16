import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { LuTrophy } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { InertiaLink } from '@/common/components/InertiaLink';
import { usePageProps } from '@/common/hooks/usePageProps';

import { ClaimActionButton } from './ClaimActionButton';
import { RequestSetToggleButton } from './RequestSetToggleButton';

export const AchievementSetEmptyState: FC = () => {
  const { auth, backingGame, setRequestData } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  if (!setRequestData) {
    return null;
  }

  return (
    <div className="mb-16 w-full rounded bg-embed p-8 text-neutral-500 light:border light:border-embed-highlight light:bg-neutral-50">
      <div className="flex flex-col items-center gap-4">
        <div className="flex flex-col items-center gap-2">
          <LuTrophy className="size-12 text-neutral-700" />
        </div>

        <div className="flex flex-col items-center gap-1 text-balance text-center">
          <p className="text-base text-neutral-400">{t('No achievements yet')}</p>
          <p>{t('Set requests help developers decide what games to work on next.')}</p>
        </div>

        <div className="mt-2 flex items-center gap-2">
          <ClaimActionButton />
          <RequestSetToggleButton />
        </div>

        <div className="text-center">
          <p>
            <Trans
              i18nKey="requestsFromPlayers"
              values={{
                count: setRequestData.totalRequests,
                val: setRequestData.totalRequests,
              }}
              components={{
                // eslint-disable-next-line jsx-a11y/anchor-has-content -- this is passed in by the consumer
                1: <a href={`/setRequestors.php?g=${backingGame.id}`} />,
              }}
            />
          </p>

          {auth?.user ? (
            <p>
              <Trans
                i18nKey="myRemainingRequests2"
                values={{
                  count: setRequestData.userRequestsRemaining,
                  val: setRequestData.userRequestsRemaining,
                }}
                components={{
                  1: (
                    <InertiaLink
                      href={route('game.request.user', { user: auth.user.displayName })}
                      prefetch="desktop-hover-only"
                    />
                  ),
                }}
              />
            </p>
          ) : null}
        </div>
      </div>
    </div>
  );
};
