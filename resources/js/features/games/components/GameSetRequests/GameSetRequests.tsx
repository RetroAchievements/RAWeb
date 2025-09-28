import type { FC } from 'react';
import { useMemo } from 'react';
import { useTranslation } from 'react-i18next';

import { GameAvatar } from '@/common/components/GameAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';
import { PatreonSupporterItem } from '@/features/patreon-supporters/components/PatreonSupporterItem';

export const GameSetRequests: FC = () => {
  const { game, deferredRequestors, initialRequestors, totalCount } =
    usePageProps<App.Community.Data.GameSetRequestsPageProps>();
  const { t } = useTranslation();

  // Combine initial and deferred requestors once deferred props finish loading.
  const allRequestors = useMemo(() => {
    const requestors = [...initialRequestors];
    if (deferredRequestors) {
      requestors.push(...deferredRequestors);
    }

    return requestors;
  }, [initialRequestors, deferredRequestors]);

  return (
    <div className="flex flex-col gap-5">
      <h1>{'List of Set Requests'}</h1>

      <div>
        <GameAvatar {...game} size={96} />
      </div>

      <div>{'A set for this game has been requested by the following users:'}</div>

      <div>
        <h2 className="border-b-0 text-center text-sm font-semibold sm:text-left sm:text-xl">
          {t('All requestors ({{val, number}})', { val: totalCount })}
        </h2>
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4">
          {allRequestors.map((supporter) => (
            <PatreonSupporterItem key={supporter.id} supporter={supporter} />
          ))}
        </div>

        {!deferredRequestors && initialRequestors.length < totalCount ? (
          <div className="mt-6 text-center text-text-muted">{t('Loading...')}</div>
        ) : null}
      </div>
    </div>
  );
};
