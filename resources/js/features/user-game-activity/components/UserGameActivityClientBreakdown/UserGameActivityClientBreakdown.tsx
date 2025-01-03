import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { useFormatPercentage } from '@/common/hooks/useFormatPercentage';
import { usePageProps } from '@/common/hooks/usePageProps';

export const UserGameActivityClientBreakdown: FC = () => {
  const { activity } = usePageProps<App.Platform.Data.PlayerGameActivityPageProps>();

  const { t } = useTranslation();

  const { formatPercentage } = useFormatPercentage();

  const { clientBreakdown } = activity;

  if (!clientBreakdown.length) {
    return (
      <div className="flex flex-col">
        <p className="font-semibold">{t('Emulator usage breakdown')}</p>
        <p className="text-neutral-500">{t('No emulator usage data is available.')}</p>
      </div>
    );
  }

  // Sort by how heavily-used the emulator was by the user.
  const sortedClients = [...clientBreakdown].sort(
    (a, b) => b.durationPercentage - a.durationPercentage,
  );

  // Show all clients directly if there are 3 or fewer, otherwise show the top 2 with the rest in tooltip.
  const topClients = sortedClients.slice(0, sortedClients.length <= 3 ? 3 : 2);
  const remainingClients = sortedClients.slice(topClients.length);

  return (
    <div className="flex flex-col">
      <p className="font-semibold">{t('Emulator usage breakdown')}</p>

      <ol>
        {topClients.map((topClient) => (
          <li key={topClient.clientIdentifier}>
            {formatPercentage(topClient.durationPercentage / 100)}
            {' - '}
            {topClient.clientIdentifier}
          </li>
        ))}

        {remainingClients.length ? (
          <BaseTooltip>
            <BaseTooltipTrigger>
              <span className="text-neutral-500">
                {t('+{{count}} more', { count: remainingClients.length })}
              </span>
            </BaseTooltipTrigger>

            <BaseTooltipContent className="max-w-md text-xs">
              <ol>
                {remainingClients.map((topClient) => (
                  <li key={topClient.clientIdentifier}>
                    {formatPercentage(topClient.durationPercentage / 100)}
                    {' - '}
                    {topClient.clientIdentifier}
                  </li>
                ))}
              </ol>
            </BaseTooltipContent>
          </BaseTooltip>
        ) : null}
      </ol>
    </div>
  );
};
