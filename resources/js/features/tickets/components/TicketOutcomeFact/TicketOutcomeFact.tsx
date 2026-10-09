import type { FC } from 'react';
import { Trans } from 'react-i18next';

import { useFormatCoarseDuration } from '@/common/hooks/useFormatCoarseDuration';

import { TicketDateTooltip } from '../TicketDateTooltip';
import { TicketUserValue } from '../TicketUserValue';

const resolutionSentences = {
  fixed: '<1>{{user}}</1> resolved this ticket as fixed <2>{{duration}}</2> after the report.',
  other:
    '<1>{{user}}</1> closed this ticket <2>{{duration}}</2> after the report. See the comments for a reason.',
  mistaken_report:
    '<1>{{user}}</1> closed this ticket <2>{{duration}}</2> after the report because it was a mistaken report.',
  not_enough_information:
    '<1>{{user}}</1> closed this ticket <2>{{duration}}</2> after the report because the report did not have enough information.',
  wrong_rom:
    '<1>{{user}}</1> closed this ticket <2>{{duration}}</2> after the report because the reporter used the wrong ROM.',
  network_problems:
    '<1>{{user}}</1> closed this ticket <2>{{duration}}</2> after the report because of network problems.',
  unable_to_reproduce:
    '<1>{{user}}</1> closed this ticket <2>{{duration}}</2> after the report because they could not reproduce the problem.',
  unable_to_debug:
    '<1>{{user}}</1> closed this ticket <2>{{duration}}</2> after the report because they could not debug the problem.',
  demoted:
    '<1>{{user}}</1> closed this ticket and demoted the achievement <2>{{duration}}</2> after the report.',
} as const satisfies Record<App.Community.Enums.TicketResolution, string>;

const demotedLeaderboardSentence =
  '<1>{{user}}</1> closed this ticket and demoted the leaderboard <2>{{duration}}</2> after the report.';

// a few really old closed tickets don't have any recorded resolution, but this is an edge case
const missingResolutionSentence =
  '<1>{{user}}</1> closed this ticket <2>{{duration}}</2> after the report.';

type OutcomeSentenceKey =
  | (typeof resolutionSentences)[keyof typeof resolutionSentences]
  | typeof demotedLeaderboardSentence
  | typeof missingResolutionSentence;

interface OutcomeFactProps {
  ticket: App.Platform.Data.TicketListEntry;
}

export const TicketOutcomeFact: FC<OutcomeFactProps> = ({ ticket }) => {
  const { formatCoarseDuration } = useFormatCoarseDuration();

  const finishedAt = ticket.resolvedAt!;

  let sentenceKey: OutcomeSentenceKey = missingResolutionSentence;
  if (ticket.resolution === 'demoted' && ticket.ticketableType === 'leaderboard') {
    sentenceKey = demotedLeaderboardSentence;
  } else if (ticket.resolution) {
    sentenceKey = resolutionSentences[ticket.resolution];
  }

  return (
    <Trans
      i18nKey={sentenceKey}
      values={{ duration: formatCoarseDuration(ticket.createdAt, finishedAt) }}
      components={{
        1: <TicketUserValue user={ticket.resolver} />,
        2: <TicketDateTooltip date={finishedAt} />,
      }}
    />
  );
};
