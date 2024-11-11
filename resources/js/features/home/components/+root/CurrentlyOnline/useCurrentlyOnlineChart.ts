import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import { useMemo } from 'react';

import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { formatDate } from '@/common/utils/l10n/formatDate';

dayjs.extend(utc);

export function useCurrentlyOnlineChart(currentlyOnline: App.Data.CurrentlyOnline) {
  const { formatNumber } = useFormatNumber();

  const chartData = buildChartData(currentlyOnline?.logEntries ?? []);

  const maxPlayersOnline = Math.max(...(currentlyOnline?.logEntries ?? []));
  const yAxisTicks = useMemo(() => {
    const ticks = [];
    for (let i = 0; i <= maxPlayersOnline + 1000; i += 1000) {
      ticks.push(i);
    }

    return ticks;
  }, [maxPlayersOnline]);

  const formatXAxisTick = (value: string) => formatDate(dayjs(value), 'LT');
  const formatYAxisTick = (value: number) => formatNumber(value);

  return { chartData, yAxisTicks, formatXAxisTick, formatYAxisTick };
}

function buildChartData(logEntries: number[]): Array<{ time: string; playersOnline: number }> {
  const now = dayjs.utc();

  // Round down to the nearest 30 minutes.
  const latestInterval =
    now.minute() < 30 ? now.startOf('hour') : now.startOf('hour').add(30, 'minutes');

  // Calculate startTime by subtracting 47 intervals of 30 minutes.
  const startTime = latestInterval.subtract(47 * 30, 'minutes');

  return logEntries.map((count, index) => {
    const time = startTime.add(index * 30, 'minute');
    const formattedTime = formatDate(time, 'llll');

    return { time: formattedTime, playersOnline: count };
  });
}
