import dayjs from 'dayjs';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from 'recharts';

import {
  type BaseChartConfig,
  BaseChartContainer,
  BaseChartTooltip,
  BaseChartTooltipContent,
} from '@/common/components/+vendor/BaseChart';
import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { formatDate } from '@/common/utils/l10n/formatDate';

import { HomeHeading } from '../../HomeHeading';

const chartConfig = {
  playersOnline: {
    label: 'Players Online',
    color: '#c39d2a',
  },
} satisfies BaseChartConfig;

export const CurrentlyOnline: FC = () => {
  const { t } = useTranslation();

  const { formatNumber } = useFormatNumber();

  const chartData = buildMockChartData();

  return (
    <div className="flex flex-col gap-2.5">
      <HomeHeading>{t('Currently Online')}</HomeHeading>

      <div className="flex w-full items-center justify-between">
        <div className="flex items-center gap-2">
          <div className="h-2.5 w-2.5 rounded-full bg-green-500" />
          <p>
            <span className="font-bold">{formatNumber(4124)}</span>{' '}
            {t('players are currently online.')}
          </p>
        </div>

        <p className="text-muted cursor-default italic transition hover:text-neutral-300 hover:light:text-neutral-950">
          {t('All-time High: {{val, number}} ({{date}})', { val: '4,494', date: 'Sept 21, 2024' })}
        </p>
      </div>

      <div className="rounded bg-embed p-4">
        <BaseChartContainer config={chartConfig} className="w-full" style={{ height: 160 }}>
          <AreaChart accessibilityLayer={true} data={chartData}>
            <CartesianGrid />

            <XAxis
              dataKey="time"
              tickLine={false}
              axisLine={false}
              tickMargin={12}
              tickFormatter={(value) => formatDate(dayjs(value), 'LT')}
            />
            <YAxis tickFormatter={(value) => formatNumber(value)} tickMargin={8} />

            <BaseChartTooltip content={<BaseChartTooltipContent />} />

            <defs>
              <linearGradient id="fillPlayersOnline" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%" stopColor="var(--color-playersOnline)" stopOpacity={0.8} />
                <stop offset="95%" stopColor="var(--color-playersOnline)" stopOpacity={0.1} />
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

const mockPlayersOnlineLogData = [
  3736, 3743, 3736, 3967, 3793, 3813, 3722, 3604, 3517, 3638, 3573, 3547, 3553, 3386, 3446, 3214,
  3027, 2783, 2757, 2604, 2474, 2258, 2203, 2024, 2010, 1883, 1847, 1814, 1859, 1937, 2078, 2020,
  2062, 2300, 2338, 2618, 2789, 2892, 3221, 3637, 3627, 3732, 3711, 3854, 3898, 4049, 4021, 4124,
  4120, 4122,
];

function buildMockChartData() {
  const startTime = new Date('October 20, 2024 00:00:00');

  return mockPlayersOnlineLogData.map((count, index) => {
    const THIRTY_MINUTES = 30 * 60000;

    const time = new Date(startTime.getTime() + index * THIRTY_MINUTES);
    const hours = time.getHours() % 12 || 12;
    const minutes = time.getMinutes().toString().padStart(2, '0');
    const ampm = time.getHours() >= 12 ? 'PM' : 'AM';
    const constructedTime = `Oct 20, 2024, ${hours}:${minutes}:00 ${ampm}`;

    const formattedTime = formatDate(dayjs(constructedTime), 'llll');

    return { time: formattedTime, playersOnline: count };
  });
}
