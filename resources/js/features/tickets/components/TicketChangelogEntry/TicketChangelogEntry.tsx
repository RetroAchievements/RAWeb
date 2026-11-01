import type { FC, ReactNode } from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { DiffTimestamp } from '@/common/components/DiffTimestamp';
import { UserAvatar } from '@/common/components/UserAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { TranslationKey } from '@/types/i18next';

import { TicketStateGlyph } from '../TicketStateGlyph';

interface PersonEntrySentences {
  asCommentFooter: TranslationKey;
  withoutUser: TranslationKey;
  withUser: TranslationKey;
}

interface AutomatedEntrySentences {
  automated: TranslationKey;
}

type EntrySentences = PersonEntrySentences | AutomatedEntrySentences;

const typeSentences = {
  resolved: {
    asCommentFooter: 'Resolved this ticket as fixed.',
    withoutUser: 'This ticket was resolved as fixed.',
    withUser: '<1>{{user}}</1> resolved this ticket as fixed.',
  },
  requested: {
    asCommentFooter: 'Asked the reporter for more information.',
    withoutUser: 'The reporter was asked for more information.',
    withUser: '<1>{{user}}</1> asked the reporter for more information.',
  },
  returned: {
    asCommentFooter: 'Moved this ticket from Request back to Open.',
    withoutUser: 'This ticket was moved from Request back to Open.',
    withUser: '<1>{{user}}</1> moved this ticket from Request back to Open.',
  },
  approved: {
    asCommentFooter: 'Approved this ticket.',
    withoutUser: 'This ticket was approved.',
    withUser: '<1>{{user}}</1> approved this ticket.',
  },
  reopened: {
    asCommentFooter: 'Reopened this ticket.',
    withoutUser: 'This ticket was reopened.',
    withUser: '<1>{{user}}</1> reopened this ticket.',
  },
  reminded: {
    automated: 'We reminded the reporter to reply.',
  },
} as const satisfies Record<
  Exclude<App.Platform.Enums.TicketChangelogEntryType, 'closed'>,
  EntrySentences
>;

const resolutionSentences = {
  fixed: typeSentences.resolved,
  other: {
    asCommentFooter: 'Closed this ticket.',
    withoutUser: 'This ticket was closed. See the comments for a reason.',
    withUser: '<1>{{user}}</1> closed this ticket. See the comments for a reason.',
  },
  mistaken_report: {
    asCommentFooter: 'Closed this ticket because it was a mistaken report.',
    withoutUser: 'This ticket was closed because it was a mistaken report.',
    withUser: '<1>{{user}}</1> closed this ticket because it was a mistaken report.',
  },
  not_enough_information: {
    asCommentFooter: 'Closed this ticket because the report did not have enough information.',
    withoutUser: 'This ticket was closed because the report did not have enough information.',
    withUser:
      '<1>{{user}}</1> closed this ticket because the report did not have enough information.',
  },
  no_reply_from_reporter: {
    automated:
      'We closed this ticket because the reporter did not reply. A reply from the reporter will reopen it.',
  },
  wrong_rom: {
    asCommentFooter: 'Closed this ticket because the reporter used the wrong ROM.',
    withoutUser: 'This ticket was closed because the reporter used the wrong ROM.',
    withUser: '<1>{{user}}</1> closed this ticket because the reporter used the wrong ROM.',
  },
  network_problems: {
    asCommentFooter: 'Closed this ticket because of network problems.',
    withoutUser: 'This ticket was closed because of network problems.',
    withUser: '<1>{{user}}</1> closed this ticket because of network problems.',
  },
  unable_to_reproduce: {
    asCommentFooter: 'Closed this ticket because the problem could not be reproduced.',
    withoutUser: 'This ticket was closed because the problem could not be reproduced.',
    withUser: '<1>{{user}}</1> closed this ticket because they could not reproduce the problem.',
  },
  unable_to_debug: {
    asCommentFooter: 'Closed this ticket because the problem could not be debugged.',
    withoutUser: 'This ticket was closed because the problem could not be debugged.',
    withUser: '<1>{{user}}</1> closed this ticket because they could not debug the problem.',
  },
  demoted: {
    asCommentFooter: 'Closed this ticket and demoted the achievement.',
    withoutUser: 'This ticket was closed and the achievement was demoted.',
    withUser: '<1>{{user}}</1> closed this ticket and demoted the achievement.',
  },
} as const satisfies Record<App.Community.Enums.TicketResolution, EntrySentences>;

const demotedLeaderboardSentences = {
  asCommentFooter: 'Closed this ticket and demoted the leaderboard.',
  withoutUser: 'This ticket was closed and the leaderboard was demoted.',
  withUser: '<1>{{user}}</1> closed this ticket and demoted the leaderboard.',
} as const satisfies EntrySentences;

// "Resolved as removed" comments from ~2017 did not record a resolution state.
const missingResolutionSentences = {
  asCommentFooter: 'Closed this ticket.',
  withoutUser: 'This ticket was closed.',
  withUser: '<1>{{user}}</1> closed this ticket.',
} as const satisfies EntrySentences;

const typeStates = {
  resolved: 'resolved',
  closed: 'closed',
  requested: 'request',
  returned: 'open',
  approved: 'open',
  reopened: 'open',
  reminded: 'request',
} as const satisfies Record<
  App.Platform.Enums.TicketChangelogEntryType,
  App.Community.Enums.TicketState
>;

interface TicketChangelogEntryProps {
  entry: App.Platform.Data.TicketChangelogEntry;
  ticketableType: App.Platform.Enums.TicketableType;

  variant?: 'base' | 'commentFooter';
}

export const TicketChangelogEntry: FC<TicketChangelogEntryProps> = ({
  entry,
  ticketableType,
  variant = 'base',
}) => {
  const { t } = useTranslation();

  const sentences = getEntrySentences(entry, ticketableType);

  if (variant === 'commentFooter') {
    return (
      <p className="flex items-center gap-1.5 text-xs text-neutral-500">
        <TicketStateGlyph state={typeStates[entry.type]} className="[&>svg]:size-3.5" />

        <span>{t('automated' in sentences ? sentences.automated : sentences.asCommentFooter)}</span>
      </p>
    );
  }

  let sentence: ReactNode;
  if ('automated' in sentences) {
    sentence = t(sentences.automated);
  } else if (entry.user) {
    sentence = (
      <Trans
        i18nKey={sentences.withUser}
        components={{
          1: <UserAvatar {...entry.user} showImage={false} wrapperClassName="inline-flex" />,
        }}
      />
    );
  } else {
    sentence = t(sentences.withoutUser);
  }

  return (
    <li className="do-not-highlight flex w-full items-center gap-4 p-2">
      <div className="flex w-8 justify-center">
        <TicketStateGlyph state={typeStates[entry.type]} />
      </div>

      <p className="text-xs text-neutral-500">
        {sentence} <EntryTimestamp at={entry.createdAt} />
      </p>
    </li>
  );
};

function getEntrySentences(
  entry: App.Platform.Data.TicketChangelogEntry,
  ticketableType: App.Platform.Enums.TicketableType,
) {
  if (entry.type !== 'closed') {
    return typeSentences[entry.type];
  }

  if (entry.resolution === 'demoted' && ticketableType === 'leaderboard') {
    return demotedLeaderboardSentences;
  }

  if (entry.resolution) {
    return resolutionSentences[entry.resolution];
  }

  return missingResolutionSentences;
}

interface EntryTimestampProps {
  at: string;
}

const EntryTimestamp: FC<EntryTimestampProps> = ({ at }) => {
  const { auth } = usePageProps();

  return (
    <span className="smalldate">
      <DiffTimestamp
        asAbsoluteDate={auth?.user.preferences.prefersAbsoluteDates ?? false}
        at={at}
      />
    </span>
  );
};
