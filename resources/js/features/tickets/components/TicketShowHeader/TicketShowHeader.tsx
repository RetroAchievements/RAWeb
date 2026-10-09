import type { FC } from 'react';
import { Trans } from 'react-i18next';
import { route } from 'ziggy-js';

import { AchievementAvatar } from '@/common/components/AchievementAvatar';
import { DiffTimestamp } from '@/common/components/DiffTimestamp';
import { GameTitle } from '@/common/components/GameTitle';
import { InertiaLink } from '@/common/components/InertiaLink';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useTicketStateLabel } from '../../hooks/useTicketStateLabel';
import { useTicketTypeLabels } from '../../hooks/useTicketTypeLabels';
import { ticketShowEntityLinkClassNames } from '../../utils/ticketShowEntityLinkClassNames';
import { TicketStateGlyph } from '../TicketStateGlyph';
import { TicketUserValue } from '../TicketUserValue';

const titleLabelClassName =
  'font-semibold text-neutral-100 [a:hover_&]:underline light:text-neutral-900';

export const TicketShowHeader: FC = () => {
  const { achievement, ticket } = usePageProps<App.Platform.Data.TicketShowPageProps>();

  const { buildTicketStateLabel } = useTicketStateLabel();
  const ticketTypeLabels = useTicketTypeLabels();

  return (
    <div className="flex gap-3">
      {achievement ? (
        <AchievementAvatar {...achievement} showLabel={false} size={48} />
      ) : (
        <a
          href={route('leaderboard.show', { leaderboard: ticket.ticketableId })}
          className="flex-none"
        >
          <img
            src={ticket.game.badgeUrl}
            alt={ticket.game.title}
            width={48}
            height={48}
            className="size-12 rounded-sm"
          />
        </a>
      )}

      <div className="flex min-w-0 flex-col gap-1">
        <h1 className="text-h3 mb-0 border-b-0 pb-0">
          {achievement ? (
            <>
              <InertiaLink
                href={route('achievement.show', { achievement: achievement.id })}
                className={ticketShowEntityLinkClassNames.wrapper}
              >
                <span className={titleLabelClassName}>{ticket.ticketableTitle}</span>
              </InertiaLink>

              <span className="text-neutral-500">{` (${achievement.points})`}</span>
            </>
          ) : (
            <a
              href={route('leaderboard.show', { leaderboard: ticket.ticketableId })}
              className={ticketShowEntityLinkClassNames.wrapper}
            >
              <span className={titleLabelClassName}>{ticket.ticketableTitle}</span>
            </a>
          )}
        </h1>

        <p className="flex items-center gap-1 text-neutral-400">
          <InertiaLink
            href={route('game.show', { game: ticket.game.id })}
            className={ticketShowEntityLinkClassNames.wrapper}
          >
            <span className={ticketShowEntityLinkClassNames.label}>
              <GameTitle title={ticket.game.title} />
            </span>
          </InertiaLink>

          <span aria-hidden="true">{'·'}</span>
          <span>{ticket.game.system!.nameShort}</span>
        </p>

        <div className="overflow-x-clip text-neutral-400">
          <div className="-ml-5 flex flex-wrap items-center gap-y-1">
            <span className="flex items-center gap-1.5 pl-5 text-neutral-200 light:text-neutral-800">
              <TicketStateGlyph state={ticket.state} />
              {buildTicketStateLabel(ticket.state, ticket.resolution)}
            </span>

            <span className="flex items-center">
              <StatusSeparator />
              {ticketTypeLabels[ticket.type]}
            </span>

            <span className="flex items-center">
              <StatusSeparator />

              <span className="flex flex-wrap items-center gap-x-1">
                <Trans
                  i18nKey="Reported by <1>{{user}}</1> <2>{{timeAgo}}</2>"
                  components={{
                    1: <TicketUserValue user={ticket.reporter} variant="quiet" />,
                    2: <DiffTimestamp at={ticket.createdAt} />,
                  }}
                />
              </span>
            </span>
          </div>
        </div>
      </div>
    </div>
  );
};

const StatusSeparator: FC = () => {
  return (
    <span aria-hidden="true" className="w-5 flex-none text-center">
      {'·'}
    </span>
  );
};
