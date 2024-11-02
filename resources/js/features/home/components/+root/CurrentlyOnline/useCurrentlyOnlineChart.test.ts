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

    const current = result.current as ReturnType<typeof useCurrentlyOnlineChart>;

    // ACT
    const formatted = current.formatXAxisTick(dayjs.utc('2024-08-07').toISOString());

    // ASSERT
    expect(formatted).toEqual('8:00 PM');
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

    const current = result.current as ReturnType<typeof useCurrentlyOnlineChart>;

    // ACT
    const formatted = current.formatYAxisTick(1000);

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

    const current = result.current as ReturnType<typeof useCurrentlyOnlineChart>;

    // ASSERT
    const firstEntryTime = dayjs(current.chartData[0].time);
    const lastEntryTime = dayjs(current.chartData[current.chartData.length - 1].time);

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

    const current = result.current as ReturnType<typeof useCurrentlyOnlineChart>;

    // ASSERT
    const firstEntryTime = dayjs(current.chartData[0].time);
    const lastEntryTime = dayjs(current.chartData[current.chartData.length - 1].time);

    expect(firstEntryTime.format('HH:mm')).toBe('11:00');
    expect(lastEntryTime.format('HH:mm')).toBe('10:30');
  });
});
