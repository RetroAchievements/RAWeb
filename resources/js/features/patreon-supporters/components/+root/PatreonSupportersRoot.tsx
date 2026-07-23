import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { UserGridLinkItem } from '@/common/components/UserGridLinkItem';
import { usePageProps } from '@/common/hooks/usePageProps';

import { BecomePatronCard } from '../BecomePatronCard';
import { SupporterTierSection } from '../SupporterTierSection';

export const PatreonSupportersRoot: FC = () => {
  const {
    config,
    deferredTier1Supporters,
    deferredTier2Supporters,
    initialTier1Supporters,
    initialTier2Supporters,
    recentSupporters,
    tier1Count,
    tier2Count,
  } = usePageProps<App.Community.Data.PatreonSupportersPageProps>();
  const { t } = useTranslation();

  return (
    <div className="flex flex-col gap-12">
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
              <UserGridLinkItem key={supporter.id} user={supporter} />
            ))}
          </div>
        </div>
      ) : null}

      {tier2Count > 0 ? (
        <SupporterTierSection
          heading={t('$2 Supporters ({{val, number}})', { val: tier2Count })}
          initialSupporters={initialTier2Supporters}
          deferredSupporters={deferredTier2Supporters}
          totalCount={tier2Count}
        />
      ) : null}

      <SupporterTierSection
        heading={t('$1 Supporters ({{val, number}})', { val: tier1Count })}
        initialSupporters={initialTier1Supporters}
        deferredSupporters={deferredTier1Supporters}
        totalCount={tier1Count}
      />
    </div>
  );
};
