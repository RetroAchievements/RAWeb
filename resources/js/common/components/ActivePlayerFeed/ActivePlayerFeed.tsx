import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import * as motion from 'motion/react-m';
import { type FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { LuSearch } from 'react-icons/lu';

import { BaseButton } from '../+vendor/BaseButton';
import { EmptyState } from '../EmptyState';
import { ActivePlayerFeedList } from './ActivePlayerFeedList';
import { ActivePlayerSearchBar } from './ActivePlayerSearchBar';
import { useActivePlayerData } from './useActivePlayerData';
import { useActivePlayerScrollObserver } from './useActivePlayerScrollObserver';
import { useActivePlayerSearch } from './useActivePlayerSearch';

dayjs.extend(utc);

interface ActivePlayerFeedProps {
  initialActivePlayers: App.Data.PaginatedData<App.Community.Data.ActivePlayer>;

  hasSearchBar?: boolean;
  persistedSearchValue?: string;
}

export const ActivePlayerFeed: FC<ActivePlayerFeedProps> = ({
  initialActivePlayers,
  persistedSearchValue,
  hasSearchBar = true,
}) => {
  const { t } = useTranslation();

  const { hasScrolled, scrollRef } = useActivePlayerScrollObserver();

  const { canShowSearchBar, handleSearch, hasSearched, searchValue, setCanShowSearchBar } =
    useActivePlayerSearch({ persistedSearchValue });

  const isInfiniteQueryEnabled = hasSearchBar && (hasSearched || hasScrolled);

  const { loadMore, players } = useActivePlayerData({
    initialActivePlayers,
    isInfiniteQueryEnabled,
    searchValue,
  });

  return (
    <div data-testid="active-player-feed">
      <div className="mb-1 flex w-full items-center justify-between">
        <p data-testid="players-label">
          <Trans
            i18nKey="playersInGameLabel"
            count={initialActivePlayers.total}
            components={{ 1: <span className="font-bold" />, 2: <span className="font-bold" /> }}
            values={{ visible: players.length, total: initialActivePlayers.unfilteredTotal }}
          />
        </p>

        {hasSearchBar ? (
          <BaseButton
            size="sm"
            onClick={() => setCanShowSearchBar((prev) => !prev)}
            aria-label={t('Search active players')}
          >
            <LuSearch className="h-4 w-4" />
          </BaseButton>
        ) : null}
      </div>

      {hasSearchBar ? (
        <motion.div
          initial={false}
          animate={{ height: canShowSearchBar ? 50 : 0, opacity: canShowSearchBar ? 1 : 0 }}
          transition={{ duration: 0.15 }}
        >
          <ActivePlayerSearchBar
            onSearch={handleSearch}
            persistedSearchValue={persistedSearchValue}
          />
        </motion.div>
      ) : null}

      <div ref={scrollRef} className="z-50 h-[325px] w-full overflow-y-auto rounded bg-embed">
        {players.length ? (
          <ActivePlayerFeedList players={players} onLoadMore={loadMore} />
        ) : (
          <EmptyState>{t("Couldn't find any active players.")}</EmptyState>
        )}
      </div>
    </div>
  );
};
