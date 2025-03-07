import type { NameType, Payload, ValueType } from 'recharts/types/component/DefaultTooltipContent';

import { renderHook } from '@/test';
import { createPlayerGame } from '@/test/factories';

import { getUserBucketIndexes } from './getUserBucketIndexes';
import { useAchievementDistributionChart } from './useAchievementDistributionChart';

vi.mock('./getUserBucketIndexes');

describe('Hook: useAchievementDistributionChart', () => {
  beforeEach(() => {
    vi.resetAllMocks();
    vi.mocked(getUserBucketIndexes).mockReturnValue({
      userHardcoreIndex: 1,
      userSoftcoreIndex: 2,
    });
  });

  const mockBuckets: App.Platform.Data.PlayerAchievementChartBucket[] = [
    { start: 0, end: 5, softcore: 10, hardcore: 5 },
    { start: 6, end: 10, softcore: 20, hardcore: 15 },
    { start: 11, end: 15, softcore: 30, hardcore: 25 },
  ];

  it('renders without crashing', () => {
    // ARRANGE
    const mockPlayerGame = createPlayerGame({
      achievementsUnlocked: 25,
      achievementsUnlockedHardcore: 15,
      achievementsUnlockedSoftcore: 10,
    });

    // ACT
    const { result } = renderHook(() =>
      useAchievementDistributionChart({
        buckets: mockBuckets,
        playerGame: mockPlayerGame,
      }),
    );

    // ASSERT
    expect(result.current).toBeDefined();
  });

  it('returns the correct chart configuration', () => {
    // ARRANGE
    const mockPlayerGame = createPlayerGame({
      achievementsUnlocked: 25,
      achievementsUnlockedHardcore: 15,
      achievementsUnlockedSoftcore: 10,
    });

    // ACT
    const { result } = renderHook(() =>
      useAchievementDistributionChart({
        buckets: mockBuckets,
        playerGame: mockPlayerGame,
      }),
    );

    // ASSERT
    expect(result.current.chartConfig).toEqual({
      softcore: {
        label: 'Softcore Players',
        color: '#737373',
      },
      hardcore: {
        label: 'Hardcore Players',
        color: '#cc9900',
      },
    });
  });

  it('calls getUserBucketIndexes with the correct parameters', () => {
    // ARRANGE
    const mockPlayerGame = createPlayerGame({
      achievementsUnlocked: 25,
      achievementsUnlockedHardcore: 15,
      achievementsUnlockedSoftcore: 10,
    });

    // ACT
    renderHook(() =>
      useAchievementDistributionChart({
        buckets: mockBuckets,
        playerGame: mockPlayerGame,
      }),
    );

    // ASSERT
    expect(getUserBucketIndexes).toHaveBeenCalledWith(mockBuckets, mockPlayerGame);
  });

  it('returns the correct user achievement counts when playerGame is provided', () => {
    // ARRANGE
    const mockPlayerGame = createPlayerGame({
      achievementsUnlocked: 25,
      achievementsUnlockedHardcore: 15,
      achievementsUnlockedSoftcore: 10,
    });

    // ACT
    const { result } = renderHook(() =>
      useAchievementDistributionChart({
        buckets: mockBuckets,
        playerGame: mockPlayerGame,
      }),
    );

    // ASSERT
    expect(result.current.userAchievementCounts).toEqual({
      softcore: 25,
      hardcore: 15,
    });
  });

  it('returns null for userAchievementCounts when playerGame is null', () => {
    // ACT
    const { result } = renderHook(() =>
      useAchievementDistributionChart({
        buckets: mockBuckets,
        playerGame: null,
      }),
    );

    // ASSERT
    expect(result.current.userAchievementCounts).toBeNull();
  });

  it('returns the correct user hardcore and softcore indexes', () => {
    // ARRANGE
    const mockPlayerGame = createPlayerGame({
      achievementsUnlocked: 25,
      achievementsUnlockedHardcore: 15,
      achievementsUnlockedSoftcore: 10,
    });

    // ACT
    const { result } = renderHook(() =>
      useAchievementDistributionChart({
        buckets: mockBuckets,
        playerGame: mockPlayerGame,
      }),
    );

    // ASSERT
    expect(result.current.userHardcoreIndex).toEqual(1);
    expect(result.current.userSoftcoreIndex).toEqual(2);
  });

  describe('Function: formatTooltipLabel', () => {
    it('returns the correct label for a valid payload', () => {
      // ARRANGE
      const mockPlayerGame = createPlayerGame({
        achievementsUnlocked: 25,
        achievementsUnlockedHardcore: 15,
        achievementsUnlockedSoftcore: 10,
      });

      const { result } = renderHook(() =>
        useAchievementDistributionChart({
          buckets: mockBuckets,
          playerGame: mockPlayerGame,
        }),
      );

      const mockPayload = [
        {
          payload: { start: 6, end: 10 } as App.Platform.Data.PlayerAchievementChartBucket,
        },
      ] as Payload<ValueType, NameType>[];

      // ACT
      const label = result.current.formatTooltipLabel('test', mockPayload);

      // ASSERT
      expect(label).toEqual('Earned 6–10 achievements');
    });

    it('returns an empty string for an empty payload', () => {
      // ARRANGE
      const mockPlayerGame = createPlayerGame({
        achievementsUnlocked: 25,
        achievementsUnlockedHardcore: 15,
        achievementsUnlockedSoftcore: 10,
      });

      const { result } = renderHook(() =>
        useAchievementDistributionChart({
          buckets: mockBuckets,
          playerGame: mockPlayerGame,
        }),
      );

      // ACT
      const label = result.current.formatTooltipLabel('test', []);

      // ASSERT
      expect(label).toEqual('');
    });

    it('formats tooltip label correctly when start and end are the same', () => {
      // ARRANGE
      const mockPlayerGame = createPlayerGame({
        achievementsUnlocked: 25,
        achievementsUnlockedHardcore: 15,
        achievementsUnlockedSoftcore: 10,
      });

      const { result } = renderHook(() =>
        useAchievementDistributionChart({
          buckets: mockBuckets,
          playerGame: mockPlayerGame,
        }),
      );

      const mockPayload = [
        {
          payload: { start: 5, end: 5 } as App.Platform.Data.PlayerAchievementChartBucket,
        },
      ] as Payload<ValueType, NameType>[];

      // ACT
      const label = result.current.formatTooltipLabel('test', mockPayload);

      // ASSERT
      expect(label).toEqual('Earned 5 achievements');
    });
  });

  describe('Function: formatXAxisTick', () => {
    it('formats the tick correctly', () => {
      // ARRANGE
      const mockPlayerGame = createPlayerGame({
        achievementsUnlocked: 25,
        achievementsUnlockedHardcore: 15,
        achievementsUnlockedSoftcore: 10,
      });

      const { result } = renderHook(() =>
        useAchievementDistributionChart({
          buckets: mockBuckets,
          playerGame: mockPlayerGame,
        }),
      );

      // ACT
      const formattedTick = result.current.formatXAxisTick(1);

      // ASSERT
      expect(formattedTick).toEqual('6');
    });
  });
});
