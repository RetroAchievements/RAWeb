import type { FC, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import {
  BaseTable,
  BaseTableBody,
  BaseTableCell,
  BaseTableHead,
  BaseTableRow,
} from '@/common/components/+vendor/BaseTable';
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { InertiaLink } from '@/common/components/InertiaLink';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { TranslatedString } from '@/types/i18next';

import { useLeaderboardFormatLabels } from '../../hooks/useLeaderboardFormatLabels';
import { getGameHashDisplayLabel } from '../../utils/getGameHashDisplayLabel';
import { TicketLogicVersionLink } from '../TicketLogicVersionLink';
import { TicketUserValue } from '../TicketUserValue';

export const TicketPropertiesPanel: FC = () => {
  const { author, hasMaintainer, leaderboard, reportedTriggerVersion, ticket } =
    usePageProps<App.Platform.Data.TicketShowPageProps>();
  const { t } = useTranslation();

  const leaderboardFormatLabels = useLeaderboardFormatLabels();

  const resolvedEmulatorName = ticket.emulator?.name ?? t('Unknown');

  return (
    <div className="rounded-lg bg-embed p-1 light:border light:border-neutral-200 light:bg-white">
      <BaseTable className="overflow-hidden rounded-lg text-2xs">
        <BaseTableBody>
          <PropertyRow t_label={hasMaintainer ? t('Maintainer') : t('Developer')}>
            <TicketUserValue user={author} />
          </PropertyRow>

          <PropertyRow t_label={t('Emulator')}>
            {ticket.emulatorVersion
              ? `${resolvedEmulatorName} ${ticket.emulatorVersion}`
              : resolvedEmulatorName}
          </PropertyRow>

          {ticket.emulatorCore ? (
            <PropertyRow t_label={t('Core')}>{ticket.emulatorCore}</PropertyRow>
          ) : null}

          <PropertyRow t_label={t('Hash')}>
            {ticket.gameHash ? (
              <span className="flex flex-col items-start">
                <BaseTooltip>
                  <BaseTooltipTrigger asChild>
                    <span>
                      <InertiaLink href={route('game.hashes.index', { game: ticket.game.id })}>
                        {getGameHashDisplayLabel(ticket.gameHash)}
                      </InertiaLink>
                    </span>
                  </BaseTooltipTrigger>

                  <BaseTooltipContent>
                    {ticket.gameHash.name ?? ticket.gameHash.md5}
                  </BaseTooltipContent>
                </BaseTooltip>

                <span className="font-mono text-2xs text-neutral-500">{ticket.gameHash.md5}</span>
              </span>
            ) : (
              t('Unknown')
            )}
          </PropertyRow>

          {/* Older tickets did not record the mode. */}
          {typeof ticket.hardcore === 'boolean' ? (
            <PropertyRow t_label={t('Mode')}>
              {ticket.hardcore ? t('Hardcore') : t('Casual')}
            </PropertyRow>
          ) : null}

          {reportedTriggerVersion ? (
            <PropertyRow t_label={t('Logic at filing')}>
              <TicketLogicVersionLink
                achievementId={ticket.ticketableId}
                version={reportedTriggerVersion}
              />
            </PropertyRow>
          ) : null}

          {leaderboard ? (
            <>
              <PropertyRow t_label={t('Format')}>
                {leaderboardFormatLabels[leaderboard.format!]}
              </PropertyRow>

              <PropertyRow t_label={t('Rank order')}>
                {leaderboard.rankAsc ? t('Lower is better') : t('Higher is better')}
              </PropertyRow>
            </>
          ) : null}
        </BaseTableBody>
      </BaseTable>
    </div>
  );
};

interface PropertyRowProps {
  children: ReactNode;
  t_label: TranslatedString;
}

const PropertyRow: FC<PropertyRowProps> = ({ children, t_label }) => {
  return (
    <BaseTableRow className="first:rounded-t-lg last:rounded-b-lg">
      <BaseTableHead scope="row" className="h-auto text-right align-top text-text">
        {t_label}
      </BaseTableHead>

      <BaseTableCell className="break-all">{children}</BaseTableCell>
    </BaseTableRow>
  );
};
