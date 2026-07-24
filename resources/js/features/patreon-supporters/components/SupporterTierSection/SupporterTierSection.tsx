import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { UserGridLinkItem } from '@/common/components/UserGridLinkItem';

interface SupporterTierSectionProps {
  heading: string;
  initialSupporters: App.Data.User[];
  totalCount: number;

  deferredSupporters?: App.Data.User[] | null;
}

export const SupporterTierSection: FC<SupporterTierSectionProps> = ({
  deferredSupporters,
  heading,
  initialSupporters,
  totalCount,
}) => {
  const { t } = useTranslation();

  // Combine initial and deferred supporters once deferred props finish loading.
  const supporters = deferredSupporters
    ? [...initialSupporters, ...deferredSupporters]
    : [...initialSupporters];

  return (
    <div>
      <h2 className="border-b-0 text-center text-sm font-semibold sm:text-left sm:text-xl">
        {heading}
      </h2>

      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4">
        {supporters.map((supporter) => (
          <UserGridLinkItem key={supporter.id} user={supporter} />
        ))}
      </div>

      {!deferredSupporters && initialSupporters.length < totalCount ? (
        <div className="mt-6 text-center text-text-muted">{t('Loading...')}</div>
      ) : null}
    </div>
  );
};
