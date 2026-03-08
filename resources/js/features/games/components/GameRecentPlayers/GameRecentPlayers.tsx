import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useCookie } from 'react-use';

import { usePageProps } from '@/common/hooks/usePageProps';

import { GameRecentPlayersList } from './GameRecentPlayersList';
import { GameRecentPlayersTable } from './GameRecentPlayersTable';

export const GameRecentPlayers: FC = () => {
  const { isRichPresenceExpanded, recentPlayers } =
    usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const [, setCookieValue] = useCookie('prefers_expanded_rich_presence');
  const [isExpanded, setIsExpanded] = useState(isRichPresenceExpanded);

  // Only show a pointer cursor if at least one RP message is long enough to actually truncate.
  const hasAnyTruncatedRichPresence = recentPlayers.some((p) => p.richPresence.length >= 40);

  if (!recentPlayers?.length) {
    return null;
  }

  const handleToggleExpanded = () => {
    const next = !isExpanded;
    setIsExpanded(next);
    setCookieValue(String(next), { expires: 180, path: '/' });
  };

  return (
    <div>
      <h2 className="mb-0 border-0 text-lg font-semibold">{t('Recent Players')}</h2>

      <div className="rounded-lg bg-embed p-1">
        <div className="flex flex-col gap-2 p-1 sm:hidden">
          <GameRecentPlayersList
            canToggleExpanded={hasAnyTruncatedRichPresence}
            isExpanded={isExpanded}
            onToggleExpanded={handleToggleExpanded}
          />
        </div>

        <GameRecentPlayersTable
          canToggleExpanded={hasAnyTruncatedRichPresence}
          isExpanded={isExpanded}
          onToggleExpanded={handleToggleExpanded}
        />
      </div>
    </div>
  );
};
