import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { type FC } from 'react';
import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from 'recharts';

import {
  type BaseChartConfig,
  BaseChartContainer,
  BaseChartTooltip,
  BaseChartTooltipContent,
} from '@/common/components/+vendor/BaseChart';
import { Trans } from '@/common/components/Trans';
import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { usePageProps } from '@/common/hooks/usePageProps';
import { formatDate } from '@/common/utils/l10n/formatDate';

import { HomeHeading } from '../../HomeHeading';
import { useCurrentlyOnlineChart } from './useCurrentlyOnlineChart';

dayjs.extend(utc);

export const CurrentlyOnline: FC = () => {
  const { currentlyOnline } = usePageProps<App.Http.Data.HomePageProps>();

  const { t } = useLaravelReactI18n();

  const { chartData, yAxisTicks, formatXAxisTick, formatYAxisTick } =
    useCurrentlyOnlineChart(currentlyOnline);

  const { formatNumber } = useFormatNumber();

  const chartConfig = {
    playersOnline: {
      label: t('Users Online'),
      color: '#c39d2a',
    },
  } satisfies BaseChartConfig;

  return (
    <div className="flex flex-col gap-2.5">
      <HomeHeading>{t('Currently Online')}</HomeHeading>

      <div className="flex w-full items-center justify-between">
        <div className="flex items-center gap-2">
          <div className="h-2.5 w-2.5 rounded-full bg-green-500" />
          <p>
            {currentlyOnline?.numCurrentPlayers === 1 ? (
              <Trans
                i18nKey="<0>:userCount</0> user is currently online."
                values={{ userCount: formatNumber(currentlyOnline?.numCurrentPlayers) }}
              >
                {/* eslint-disable */}
                <span className="font-bold">
                  {formatNumber(currentlyOnline?.numCurrentPlayers)}
                </span>{' '}
                user is currently online.
                {/* eslint-enable */}
              </Trans>
            ) : (
              <Trans
                i18nKey="<0>:userCount</0> users are currently online."
                values={{ userCount: formatNumber(currentlyOnline?.numCurrentPlayers) }}
              >
                {/* eslint-disable */}
                <span className="font-bold">
                  {formatNumber(currentlyOnline?.numCurrentPlayers)}
                </span>{' '}
                users are currently online.
                {/* eslint-enable */}
              </Trans>
            )}
          </p>
        </div>

        <p className="text-muted cursor-default italic transition hover:text-neutral-300 hover:light:text-neutral-950">
          {t('All-time High: :number :date', {
            number: formatNumber(currentlyOnline?.allTimeHighPlayers),
            date: currentlyOnline?.allTimeHighDate
              ? `(${formatDate(dayjs.utc(currentlyOnline.allTimeHighDate), 'll')})`
              : '',
          })}
        </p>
      </div>

      <div className="rounded bg-embed p-4">
        <BaseChartContainer config={chartConfig} className="w-full" style={{ height: 160 }}>
          <AreaChart accessibilityLayer={true} data={chartData}>
            <CartesianGrid strokeDasharray="3 3" />

            <XAxis
              dataKey="time"
              tickLine={false}
              axisLine={false}
              tickMargin={12}
              interval={7}
              tickFormatter={formatXAxisTick}
            />
            <YAxis tickFormatter={formatYAxisTick} tickMargin={8} ticks={yAxisTicks} />

            <BaseChartTooltip content={<BaseChartTooltipContent />} />

            <defs>
              <linearGradient id="fillPlayersOnline" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%" stopColor="var(--color-playersOnline)" stopOpacity={0.8} />
                <stop offset="95%" stopColor="var(--color-playersOnline)" stopOpacity={0.28} />
              </linearGradient>
            </defs>

            <Area
              dataKey="playersOnline"
              type="natural"
              isAnimationActive={false}
              fill="url(#fillPlayersOnline)"
              fillOpacity={0.4}
              stroke="var(--color-playersOnline)"
              stackId="a"
              dot={{ fill: 'yellow', r: 2 }}
            />
          </AreaChart>
        </BaseChartContainer>
      </div>
    </div>
  );
};
