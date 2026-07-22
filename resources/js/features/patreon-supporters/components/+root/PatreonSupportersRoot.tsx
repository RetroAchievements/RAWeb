import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { UserGridLinkItem } from '@/common/components/UserGridLinkItem';
import { usePageProps } from '@/common/hooks/usePageProps';

import { BecomePatronCard } from '../BecomePatronCard';

export const PatreonSupportersRoot: FC = () => {
  const {
    config,
    deferredSupporters,
    initialSupporters,
    recentSupporters,
    tier2SupporterIds,
    totalCount,
  } = usePageProps<App.Community.Data.PatreonSupportersPageProps>();
  const { t } = useTranslation();

  // Combine initial and deferred supporters once deferred props finish loading.
  const allSupporters = deferredSupporters
    ? [...initialSupporters, ...deferredSupporters]
    : [...initialSupporters];

  // The higher supporter tier gets a subtle highlight on their card.
  const tier2Ids = new Set(tier2SupporterIds);

  return (
    <div className="flex flex-col gap-5">
      <h1>{'Patreon Supporters'}</h1>

      {config?.services.patreon.userId ? (
        <div className="flex w-full justify-center">
          <BecomePatronCard />
        </div>
      ) : null}

      {recentSupporters.length > 0 ? (
        <div>
          <h2 className="border-b-0 text-center text-sm font-semibold sm:text-left sm:text-xl">
            {t('Our Newest Patreon Supporters')}
          </h2>
          <div className="grid grid-cols-1 gap-3 rounded-lg border-2 border-embed-highlight bg-embed-highlight p-2 sm:grid-cols-2 lg:grid-cols-4 light:bg-white">
            {recentSupporters.map((supporter) => (
              <UserGridLinkItem
                key={supporter.id}
                user={supporter}
                isHighlighted={!!supporter.id && tier2Ids.has(supporter.id)}
              />
            ))}
          </div>
        </div>
      ) : null}

      <div>
        <h2 className="border-b-0 text-center text-sm font-semibold sm:text-left sm:text-xl">
          {t('All supporters ({{val, number}})', { val: totalCount })}
        </h2>
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4">
          {allSupporters.map((supporter) => (
            <UserGridLinkItem
              key={supporter.id}
              user={supporter}
              isHighlighted={!!supporter.id && tier2Ids.has(supporter.id)}
            />
          ))}
        </div>

        {!deferredSupporters && initialSupporters.length < totalCount ? (
          <div className="mt-6 text-center text-text-muted">{t('Loading...')}</div>
        ) : null}
      </div>
    </div>
  );
};
