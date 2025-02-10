import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseTable,
  BaseTableBody,
  BaseTableCell,
  BaseTableHead,
  BaseTableHeader,
  BaseTableRow,
} from '@/common/components/+vendor/BaseTable';
import { GameTitle } from '@/common/components/GameTitle';
import { InertiaLink } from '@/common/components/InertiaLink';
import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cleanHubTitle } from '@/common/utils/cleanHubTitle';

export const RelatedHubs: FC = () => {
  const { relatedHubs } = usePageProps<App.Platform.Data.HubPageProps>();

  const { t } = useTranslation();

  const { formatNumber } = useFormatNumber();

  return (
    <div className="flex flex-col">
      <h2 className="border-b-0 text-xl font-semibold">{t('Related Hubs')}</h2>

      {!relatedHubs?.length ? <p>{t('No related hubs.')}</p> : null}

      {relatedHubs?.length ? (
        <BaseTable className="table-highlight">
          <BaseTableHeader>
            <BaseTableRow className="do-not-highlight">
              <BaseTableHead>{t('Hub')}</BaseTableHead>
              <BaseTableHead className="text-right">{t('Games')}</BaseTableHead>
              <BaseTableHead className="text-right">{t('Links')}</BaseTableHead>
            </BaseTableRow>
          </BaseTableHeader>

          <BaseTableBody>
            {relatedHubs.map((relatedHub) => (
              <BaseTableRow key={`related-${relatedHub.id}`}>
                <BaseTableCell>
                  <a
                    href={route('hub.show', { gameSet: relatedHub.id })}
                    className="flex max-w-fit items-center gap-2"
                  >
                    <img
                      loading="lazy"
                      decoding="async"
                      width={32}
                      height={32}
                      src={relatedHub.badgeUrl!}
                      alt={relatedHub.title!}
                      className="rounded-sm"
                    />

                    <GameTitle title={cleanHubTitle(relatedHub.title!)} />
                  </a>
                </BaseTableCell>

                <BaseTableCell className="text-right">
                  {formatNumber(relatedHub.gameCount)}
                </BaseTableCell>
                <BaseTableCell className="text-right">
                  {formatNumber(relatedHub.linkCount)}
                </BaseTableCell>
              </BaseTableRow>
            ))}
          </BaseTableBody>
        </BaseTable>
      ) : null}
    </div>
  );
};
