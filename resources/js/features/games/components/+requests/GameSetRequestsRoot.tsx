import type { FC } from 'react';
import { useMemo } from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { EmptyState } from '@/common/components/EmptyState';
import { GameBreadcrumbs } from '@/common/components/GameBreadcrumbs';
import { GameHeading } from '@/common/components/GameHeading';
import { UserGridLinkItem } from '@/common/components/UserGridLinkItem';
import { usePageProps } from '@/common/hooks/usePageProps';

export const GameSetRequestsRoot: FC = () => {
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
      <GameBreadcrumbs game={game} system={game.system} t_currentPageLabel={t('Set Requests')} />
      <GameHeading game={game} wrapperClassName="!mb-1">
        {t('Set Requests')}
      </GameHeading>

      {allRequestors.length > 0 ? (
        <Trans
          i18nKey="gameRequestsCount"
          count={totalCount}
          values={{ val: totalCount }}
          components={{ 1: <span className="font-bold" /> }}
        />
      ) : (
        <div className="rounded-lg bg-embed">
          <EmptyState>{t('There are currently no active requests.')}</EmptyState>
        </div>
      )}

      <div>
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4">
          {allRequestors.map((supporter) => (
            <UserGridLinkItem key={supporter.id} user={supporter} />
          ))}
        </div>

        {!deferredRequestors && initialRequestors.length < totalCount ? (
          <div className="mt-6 text-center text-text-muted">{t('Loading...')}</div>
        ) : null}
      </div>
    </div>
  );
};
