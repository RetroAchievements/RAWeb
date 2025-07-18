import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';

import { renderHook } from '@/test';

import { useDiffForHumans } from './useDiffForHumans';

dayjs.extend(utc);

describe('Hook: useDiffForHumans', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useDiffForHumans());

    // ASSERT
    expect(result).toBeDefined();
  });

  it('exposes a diffForHumans function to the consumer', () => {
    // ARRANGE
    const { result } = renderHook(() => useDiffForHumans());

    // ASSERT
    expect(result.current.diffForHumans).toBeDefined();
  });

  it('given seconds is 0, always displays "just now"', () => {
    // ARRANGE
    const mockDate = dayjs.utc('2023-10-25').toDate();
    vi.setSystemTime(mockDate);

    const { result } = renderHook(() => useDiffForHumans());

    // ACT
    const diff = result.current.diffForHumans(mockDate.toISOString());

    // ASSERT
    expect(diff).toEqual('just now');
  });

  describe('Narrow style', () => {
    it('given narrow style and less than 60 seconds ago, formats with second units', () => {
      // ARRANGE
      vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

      const now = dayjs.utc();
      const thirtySecondsAgo = now.subtract(30, 'seconds');

      const { result } = renderHook(() => useDiffForHumans());

      // ACT
      const diff = result.current.diffForHumans(
        thirtySecondsAgo.toISOString(),
        undefined,
        'narrow',
      );

      // ASSERT
      expect(diff).toEqual('30s ago');
    });

    it('given narrow style and less than 60 seconds from now, formats with second units', () => {
      // ARRANGE
      vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

      const now = dayjs.utc();
      const fortyFiveSecondsFromNow = now.add(45, 'seconds');

      const { result } = renderHook(() => useDiffForHumans());

      // ACT
      const diff = result.current.diffForHumans(
        fortyFiveSecondsFromNow.toISOString(),
        undefined,
        'narrow',
      );

      // ASSERT
      expect(diff).toEqual('in 45s');
    });

    it('given narrow style and exactly 60 seconds, uses minute formatting', () => {
      // ARRANGE
      vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

      const now = dayjs.utc();
      const oneMinuteAgo = now.subtract(60, 'seconds');

      const { result } = renderHook(() => useDiffForHumans());

      // ACT
      const diff = result.current.diffForHumans(oneMinuteAgo.toISOString(), undefined, 'narrow');

      // ASSERT
      expect(diff).toEqual('1m ago');
    });
  });

  describe('Past', () => {
    it('given less than 10 seconds ago, formats correctly', () => {
      // ARRANGE
      vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

      const now = dayjs.utc();
      const fiveSecondsAgo = now.subtract(5, 'seconds');

      const { result } = renderHook(() => useDiffForHumans());

      // ACT
      const diff = result.current.diffForHumans(fiveSecondsAgo.toISOString());

      // ASSERT
      expect(diff).toEqual('just now');
    });

    it('given 30 seconds ago, formats correctly', () => {
      // ARRANGE
      vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

      const now = dayjs.utc();
      const thirtySecondsAgo = now.subtract(30, 'seconds');

      const { result } = renderHook(() => useDiffForHumans());

      // ACT
      const diff = result.current.diffForHumans(thirtySecondsAgo.toISOString());

      // ASSERT
      expect(diff).toEqual('less than a minute ago');
    });

    it('given a few minutes ago, formats correctly', () => {
      // ARRANGE
      vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

      const now = dayjs.utc();
      const threeMinutesAgo = now.subtract(3, 'minutes');

      const { result } = renderHook(() => useDiffForHumans());

      // ACT
      const diff = result.current.diffForHumans(threeMinutesAgo.toISOString());

      // ASSERT
      expect(diff).toEqual('3 minutes ago');
    });

    it('given a few hours ago, formats correctly', () => {
      // ARRANGE
      vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

      const now = dayjs.utc();
      const twoHoursAgo = now.subtract(2, 'hours');

      const { result } = renderHook(() => useDiffForHumans());

      // ACT
      const diff = result.current.diffForHumans(twoHoursAgo.toISOString());

      // ASSERT
      expect(diff).toEqual('2 hours ago');
    });

    it('given a few days ago, formats correctly', () => {
      // ARRANGE
      vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

      const now = dayjs.utc();
      const threeDaysAgo = now.subtract(3, 'days');

      const { result } = renderHook(() => useDiffForHumans());

      // ACT
      const diff = result.current.diffForHumans(threeDaysAgo.toISOString());

      // ASSERT
      expect(diff).toEqual('3 days ago');
    });

    it('given a week ago, formats correctly', () => {
      // ARRANGE
      vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

      const now = dayjs.utc();
      const oneWeekAgo = now.subtract(1, 'week');

      const { result } = renderHook(() => useDiffForHumans());

      // ACT
      const diff = result.current.diffForHumans(oneWeekAgo.toISOString());

      // ASSERT
      expect(diff).toEqual('1 week ago');
    });

    it('given a few months ago, formats correctly', () => {
      // ARRANGE
      vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

      const now = dayjs.utc();
      const twoMonthsAgo = now.subtract(2, 'months');

      const { result } = renderHook(() => useDiffForHumans());

      // ACT
      const diff = result.current.diffForHumans(twoMonthsAgo.toISOString());

      // ASSERT
      expect(diff).toEqual('2 months ago');
    });

    it('given a few years ago, formats correctly', () => {
      // ARRANGE
      vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

      const sixYearsAgo = dayjs.utc('2017-10-24'); // Explicitly set ... dayjs math struggles with leap years.

      const { result } = renderHook(() => useDiffForHumans());

      // ACT
      const diff = result.current.diffForHumans(sixYearsAgo.toISOString());

      // ASSERT
      expect(diff).toEqual('6 years ago');
    });
  });

  describe('Future', () => {
    it('given less than 10 seconds from now, formats correctly', () => {
      // ARRANGE
      vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

      const now = dayjs.utc();
      const fiveSecondsFromNow = now.add(5, 'seconds');

      const { result } = renderHook(() => useDiffForHumans());

      // ACT
      const diff = result.current.diffForHumans(fiveSecondsFromNow.toISOString());

      // ASSERT
      expect(diff).toEqual('in a few seconds');
    });

    it('given 30 seconds from now, formats correctly', () => {
      // ARRANGE
      vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

      const now = dayjs.utc();
      const thirtySecondsFromNow = now.add(30, 'seconds');

      const { result } = renderHook(() => useDiffForHumans());

      // ACT
      const diff = result.current.diffForHumans(thirtySecondsFromNow.toISOString());

      // ASSERT
      expect(diff).toEqual('in less than a minute');
    });

    it('given a few minutes from now, formats correctly', () => {
      // ARRANGE
      vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

      const now = dayjs.utc();
      const threeMinutesFromNow = now.add(3, 'minutes');

      const { result } = renderHook(() => useDiffForHumans());

      // ACT
      const diff = result.current.diffForHumans(threeMinutesFromNow.toISOString());

      // ASSERT
      expect(diff).toEqual('in 3 minutes');
    });

    it('given a few hours from now, formats correctly', () => {
      // ARRANGE
      vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

      const now = dayjs.utc();
      const twoHoursFromNow = now.add(2, 'hours');

      const { result } = renderHook(() => useDiffForHumans());

      // ACT
      const diff = result.current.diffForHumans(twoHoursFromNow.toISOString());

      // ASSERT
      expect(diff).toEqual('in 2 hours');
    });

    it('given a few days from now, formats correctly', () => {
      // ARRANGE
      vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

      const now = dayjs.utc();
      const threeDaysFromNow = now.add(3, 'days');

      const { result } = renderHook(() => useDiffForHumans());

      // ACT
      const diff = result.current.diffForHumans(threeDaysFromNow.toISOString());

      // ASSERT
      expect(diff).toEqual('in 3 days');
    });

    it('given a week from now, formats correctly', () => {
      // ARRANGE
      vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

      const now = dayjs.utc();
      const oneWeekFromNow = now.add(1, 'week');

      const { result } = renderHook(() => useDiffForHumans());

      // ACT
      const diff = result.current.diffForHumans(oneWeekFromNow.toISOString());

      // ASSERT
      expect(diff).toEqual('in 1 week');
    });

    it('given a few months from now, formats correctly', () => {
      // ARRANGE
      vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

      const now = dayjs.utc();
      const twoMonthsFromNow = now.add(2, 'months');

      const { result } = renderHook(() => useDiffForHumans());

      // ACT
      const diff = result.current.diffForHumans(twoMonthsFromNow.toISOString());

      // ASSERT
      expect(diff).toEqual('in 2 months');
    });

    it('given a few years from now, formats correctly', () => {
      // ARRANGE
      vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

      const now = dayjs.utc();
      const sixYearsFromNow = now.add(6, 'years');

      const { result } = renderHook(() => useDiffForHumans());

      // ACT
      const diff = result.current.diffForHumans(sixYearsFromNow.toISOString());

      // ASSERT
      expect(diff).toEqual('in 6 years');
    });
  });
});
