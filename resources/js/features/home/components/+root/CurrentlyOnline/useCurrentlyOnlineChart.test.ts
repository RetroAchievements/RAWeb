import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';

import { renderHook } from '@/test';

import { useCurrentlyOnlineChart } from './useCurrentlyOnlineChart';

dayjs.extend(utc);

describe('Hook: useCurrentlyOnlineChart', () => {
  afterEach(() => {
    vi.useRealTimers();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() =>
      useCurrentlyOnlineChart({
        allTimeHighDate: new Date('2024-08-07').toISOString(),
        allTimeHighPlayers: 4744,
        logEntries: [],
        numCurrentPlayers: 0,
      }),
    );

    // ASSERT
    expect(result).toBeTruthy();
  });

  it('can properly format tooltip labels', () => {
    // ARRANGE
    const { result } = renderHook(() =>
      useCurrentlyOnlineChart({
        allTimeHighDate: new Date('2024-08-07').toISOString(),
        allTimeHighPlayers: 4744,
        logEntries: [],
        numCurrentPlayers: 0,
      }),
    );

    // ACT
    const formatted = result.current.formatTooltipLabel(new Date('2023-05-07').toISOString());

    // ASSERT
    expect(formatted).toEqual('Sun, May 7, 2023 12:00 AM');
  });

  it('can properly format x-axis ticks', () => {
    // ARRANGE
    const { result } = renderHook(() =>
      useCurrentlyOnlineChart({
        allTimeHighDate: new Date('2024-08-07').toISOString(),
        allTimeHighPlayers: 4744,
        logEntries: [],
        numCurrentPlayers: 0,
      }),
    );

    // ACT
    const formatted = result.current.formatXAxisTick(dayjs.utc('2024-08-07').toISOString());

    // ASSERT
    expect(formatted).toEqual('12:00 AM');
  });

  it('can properly format y-axis ticks', () => {
    // ARRANGE
    const { result } = renderHook(() =>
      useCurrentlyOnlineChart({
        allTimeHighDate: new Date('2024-08-07').toISOString(),
        allTimeHighPlayers: 4744,
        logEntries: [],
        numCurrentPlayers: 0,
      }),
    );

    // ACT
    const formatted = result.current.formatYAxisTick(1000);

    // ASSERT
    expect(formatted).toEqual('1,000');
  });

  it('calculates time intervals for the log entries correctly when the current time is before the half hour mark', () => {
    // ARRANGE
    const mockDate = dayjs.utc('2024-08-07T10:15:00.000Z'); // 15 minutes past the hour
    vi.setSystemTime(mockDate.toDate());

    const { result } = renderHook(() =>
      useCurrentlyOnlineChart({
        allTimeHighDate: new Date('2024-08-07').toISOString(),
        allTimeHighPlayers: 4744,
        logEntries: Array(48).fill(100),
        numCurrentPlayers: 0,
      }),
    );

    // ASSERT
    const firstEntryTime = dayjs(result.current.chartData[0].time);
    const lastEntryTime = dayjs(result.current.chartData[result.current.chartData.length - 1].time);

    expect(firstEntryTime.format('HH:mm')).toBe('10:30');
    expect(lastEntryTime.format('HH:mm')).toBe('10:00');
  });

  it('calculates time intervals for the log entries correctly when the current time is after the half hour mark', () => {
    // ARRANGE
    const mockDate = dayjs.utc('2024-08-07T10:45:00.000Z'); // 45 minutes past the hour
    vi.setSystemTime(mockDate.toDate());

    const { result } = renderHook(() =>
      useCurrentlyOnlineChart({
        allTimeHighDate: new Date('2024-08-07').toISOString(),
        allTimeHighPlayers: 4744,
        logEntries: Array(48).fill(100),
        numCurrentPlayers: 0,
      }),
    );

    // ASSERT
    const firstEntryTime = dayjs(result.current.chartData[0].time);
    const lastEntryTime = dayjs(result.current.chartData[result.current.chartData.length - 1].time);

    expect(firstEntryTime.format('HH:mm')).toBe('11:00');
    expect(lastEntryTime.format('HH:mm')).toBe('10:30');
  });
});
