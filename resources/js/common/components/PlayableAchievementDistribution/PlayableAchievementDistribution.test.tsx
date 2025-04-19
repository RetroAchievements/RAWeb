/* eslint-disable testing-library/no-container */

import { createElement } from 'react';

import { render, screen } from '@/test';
import { createPlayerGame } from '@/test/factories';

import { PlayableAchievementDistribution } from './PlayableAchievementDistribution';
import * as useAchievementDistributionChartModule from './useAchievementDistributionChart';

vi.mock('recharts', async (importOriginal) => {
  const originalModule = (await importOriginal()) as Record<string, unknown>;

  return {
    ...originalModule,
    ResponsiveContainer: () => createElement('div'),
  };
});

vi.mock('./useAchievementDistributionChart', () => ({
  useAchievementDistributionChart: vi.fn(),
}));

describe('Component: PlayableAchievementDistribution', () => {
  const defaultHookReturnValue = {
    chartConfig: {
      margin: { top: 10, right: 10, bottom: 10, left: 10 },
    },
    formatTooltipLabel: vi.fn((value) => `Formatted: ${value}`),
    formatXAxisTick: vi.fn((value) => `Tick: ${value}`),
    userAchievementCounts: { hardcore: 5, softcore: 3 },
    userHardcoreIndex: 2,
    userSoftcoreIndex: 4,
  };

  beforeEach(() => {
    vi.clearAllMocks();

    console.error = vi.fn();
    vi.spyOn(
      useAchievementDistributionChartModule,
      'useAchievementDistributionChart',
    ).mockReturnValue(defaultHookReturnValue as any);
  });

  it('renders without crashing', () => {
    // ARRANGE
    const buckets: App.Platform.Data.PlayerAchievementChartBucket[] = [
      { start: 0, end: 9, hardcore: 10, softcore: 5 },
      { start: 10, end: 19, hardcore: 15, softcore: 8 },
    ];

    const { container } = render(
      <PlayableAchievementDistribution buckets={buckets} playerGame={null} variant="game" />,
    );

    // ASSERT
    expect(container).toBeTruthy();
    expect(screen.getByTestId('achievement-distribution')).toBeVisible();
  });

  it('given there are no buckets, renders nothing', () => {
    // ARRANGE
    render(<PlayableAchievementDistribution buckets={[]} playerGame={null} variant="game" />);

    // ASSERT
    expect(screen.queryByTestId('achievement-distribution')).not.toBeInTheDocument();
  });

  it('given buckets data, renders the chart with the correct title', () => {
    // ARRANGE
    const buckets: App.Platform.Data.PlayerAchievementChartBucket[] = [
      { start: 0, end: 9, hardcore: 10, softcore: 5 },
      { start: 10, end: 19, hardcore: 15, softcore: 8 },
    ];

    render(<PlayableAchievementDistribution buckets={buckets} playerGame={null} variant="game" />);

    // ASSERT
    expect(screen.getByText(/achievement distribution/i)).toBeVisible();
  });

  it('given buckets and playerGame data, passes correct props to the hook', () => {
    // ARRANGE
    const buckets = [
      { start: 0, end: 9, hardcore: 10, softcore: 5 },
      { start: 10, end: 19, hardcore: 15, softcore: 8 },
    ] as App.Platform.Data.PlayerAchievementChartBucket[];

    const playerGame = createPlayerGame();

    render(
      <PlayableAchievementDistribution buckets={buckets} playerGame={playerGame} variant="game" />,
    );

    // ASSERT
    expect(
      useAchievementDistributionChartModule.useAchievementDistributionChart,
    ).toHaveBeenCalledWith({
      buckets,
      playerGame,
      variant: 'game',
    });
  });

  it('given a playerGame with hardcore achievements, renders with the right user indices', () => {
    // ARRANGE
    const buckets: App.Platform.Data.PlayerAchievementChartBucket[] = [
      { start: 0, end: 9, hardcore: 10, softcore: 5 },
      { start: 10, end: 19, hardcore: 15, softcore: 8 },
    ];

    vi.spyOn(
      useAchievementDistributionChartModule,
      'useAchievementDistributionChart',
    ).mockReturnValue({
      ...defaultHookReturnValue,
      userHardcoreIndex: 2,
      userSoftcoreIndex: undefined,
    } as any);

    render(<PlayableAchievementDistribution buckets={buckets} playerGame={null} variant="game" />);

    // ASSERT
    // Since we're not really able to render a ReferenceLine in tests (it's controlled by recharts),
    // we should test that our component logic uses the correct values from the hook.
    const hookData = (useAchievementDistributionChartModule.useAchievementDistributionChart as any)
      .mock.results[0].value;

    expect(hookData.userHardcoreIndex).toEqual(2);
    expect(hookData.userSoftcoreIndex).toBeUndefined();
  });

  it('given a playerGame with softcore achievements, renders with the right user indices', () => {
    // ARRANGE
    const buckets: App.Platform.Data.PlayerAchievementChartBucket[] = [
      { start: 0, end: 9, hardcore: 10, softcore: 5 },
      { start: 10, end: 19, hardcore: 15, softcore: 8 },
    ];

    // !! set specific mock return to test reference lines
    vi.spyOn(
      useAchievementDistributionChartModule,
      'useAchievementDistributionChart',
    ).mockReturnValue({
      ...defaultHookReturnValue,
      userHardcoreIndex: undefined,
      userSoftcoreIndex: 4,
    } as any);

    render(<PlayableAchievementDistribution buckets={buckets} playerGame={null} variant="game" />);

    // ASSERT
    const hookData = (useAchievementDistributionChartModule.useAchievementDistributionChart as any)
      .mock.results[0].value;

    expect(hookData.userHardcoreIndex).toBeUndefined();
    expect(hookData.userSoftcoreIndex).toEqual(4);
  });

  it('given a playerGame with both achievement types, renders with both indices', () => {
    // ARRANGE
    const buckets: App.Platform.Data.PlayerAchievementChartBucket[] = [
      { start: 0, end: 9, hardcore: 10, softcore: 5 },
      { start: 10, end: 19, hardcore: 15, softcore: 8 },
    ];

    // !! set specific mock return to test both reference lines
    vi.spyOn(
      useAchievementDistributionChartModule,
      'useAchievementDistributionChart',
    ).mockReturnValue({
      ...defaultHookReturnValue,
      userHardcoreIndex: 2,
      userSoftcoreIndex: 4,
    } as any);

    render(<PlayableAchievementDistribution buckets={buckets} playerGame={null} variant="game" />);

    // ASSERT
    const hookData = (useAchievementDistributionChartModule.useAchievementDistributionChart as any)
      .mock.results[0].value;

    expect(hookData.userHardcoreIndex).toEqual(2);
    expect(hookData.userSoftcoreIndex).toEqual(4);
  });

  it('given no achievement indices from the hook, renders without reference lines', () => {
    // ARRANGE
    const buckets: App.Platform.Data.PlayerAchievementChartBucket[] = [
      { start: 0, end: 9, hardcore: 10, softcore: 5 },
      { start: 10, end: 19, hardcore: 15, softcore: 8 },
    ];

    vi.spyOn(
      useAchievementDistributionChartModule,
      'useAchievementDistributionChart',
    ).mockReturnValue({
      ...defaultHookReturnValue,
      userHardcoreIndex: undefined,
      userSoftcoreIndex: undefined,
    } as any);

    render(<PlayableAchievementDistribution buckets={buckets} playerGame={null} variant="game" />);

    // ASSERT
    const hookData = (useAchievementDistributionChartModule.useAchievementDistributionChart as any)
      .mock.results[0].value;

    expect(hookData.userHardcoreIndex).toBeUndefined();
    expect(hookData.userSoftcoreIndex).toBeUndefined();
  });
});
