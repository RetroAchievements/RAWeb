import { type ChangeEvent, type FC, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useCookie, useDebounce } from 'react-use';

import { BaseCheckbox } from '../+vendor/BaseCheckbox';
import { BaseInput } from '../+vendor/BaseInput';
import { BaseLabel } from '../+vendor/BaseLabel';

interface ActivePlayerSearchBarProps {
  onSearch: (searchValue: string) => void;

  persistedSearchCookieName?: string;
  persistedSearchValue?: string;
}

export const ActivePlayerSearchBar: FC<ActivePlayerSearchBarProps> = ({
  onSearch,
  persistedSearchValue,
  persistedSearchCookieName = 'active_players_search',
}) => {
  const { t } = useTranslation();

  const [, setPersistedActivePlayersSearch, deletePersistedActivePlayersSearch] =
    useCookie(persistedSearchCookieName);

  const [rawInputValue, setRawInputValue] = useState(persistedSearchValue ?? '');

  const [shouldRememberSearch, setShouldRememberSearch] = useState(!!persistedSearchValue);

  const isFirstRender = useRef(true);

  useDebounce(
    () => {
      // Skip on the first render. Otherwise, the callback will instantly be invoked.
      if (isFirstRender.current) {
        isFirstRender.current = false;

        return;
      }

      onSearch(rawInputValue);

      if (shouldRememberSearch) {
        setPersistedActivePlayersSearch(rawInputValue);
      }
    },
    400,
    [rawInputValue],
  );

  const handleSearchInputChange = (event: ChangeEvent<HTMLInputElement>) => {
    setRawInputValue(event.target.value);
  };

  const handleToggleRememberSearch = (isChecked: boolean) => {
    setShouldRememberSearch(isChecked);

    if (isChecked) {
      setPersistedActivePlayersSearch(rawInputValue);
    } else {
      deletePersistedActivePlayersSearch();
    }
  };

  return (
    <div className="flex items-center gap-2 rounded-lg bg-embed pr-3">
      <label className="sr-only" htmlFor="active-player-search">
        {t('Search by player name, game title, or Rich Presence...')}
      </label>

      <BaseInput
        id="active-player-search"
        placeholder={t('Search by player name, game title, or Rich Presence...')}
        value={rawInputValue}
        onChange={handleSearchInputChange}
      />

      <div className="flex items-center gap-1.5">
        <BaseLabel className="flex items-center gap-1.5 whitespace-nowrap text-menu-link">
          <BaseCheckbox
            checked={shouldRememberSearch}
            onCheckedChange={handleToggleRememberSearch}
          />

          {t('Remember my search')}
        </BaseLabel>
      </div>
    </div>
  );
};
