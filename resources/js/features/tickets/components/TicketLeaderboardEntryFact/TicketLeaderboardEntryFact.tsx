import type { FC } from 'react';
import { Trans } from 'react-i18next';

import { TicketUserValue } from '../TicketUserValue';

interface LeaderboardEntryFactProps {
  reporter: App.Data.User;

  entry?: {
    leaderboardEntry: App.Platform.Data.LeaderboardEntry;
    totalEntries: number;
  };
}

export const TicketLeaderboardEntryFact: FC<LeaderboardEntryFactProps> = ({ entry, reporter }) => {
  const reporterElement = <TicketUserValue user={reporter} />;

  if (!entry) {
    return (
      <Trans
        i18nKey="<1>{{user}}</1> has no entry yet on this leaderboard."
        components={{ 1: reporterElement }}
      />
    );
  }

  return (
    <Trans
      i18nKey="<1>{{user}}</1> has an entry on this leaderboard: #{{rank, number}} of {{total, number}}, {{score}}."
      values={{
        rank: entry.leaderboardEntry.rank,
        total: entry.totalEntries,
        score: entry.leaderboardEntry.formattedScore,
      }}
      components={{ 1: reporterElement }}
    />
  );
};
