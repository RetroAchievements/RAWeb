import { render, screen } from '@/test';

import { ReferenceLineTooltipContent } from './ReferenceLineTooltipContent';

// Mock BaseChartTooltipContent since we're not testing its implementation.
vi.mock('@/common/components/+vendor/BaseChart', () => ({
  BaseChartTooltipContent: ({ children }: { children?: React.ReactNode }) => (
    <div data-testid="base-tooltip">{children}</div>
  ),
}));

describe('Component: ReferenceLineTooltipContent', () => {
  // Create sample data to use in tests
  const sampleBuckets = [
    { id: 1, label: '0-10', count: 100 },
    { id: 2, label: '11-20', count: 200 },
    { id: 3, label: '21-30', count: 150 },
  ] as unknown as App.Platform.Data.PlayerAchievementChartBucket[];

  const samplePayload = [
    {
      payload: sampleBuckets[1],
    },
  ];

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <ReferenceLineTooltipContent
        active={true}
        payload={samplePayload}
        buckets={sampleBuckets}
        userAchievementCounts={{ softcore: 15, hardcore: 12 }}
        variant="game"
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given active is false, renders the base tooltip content', () => {
    // ARRANGE
    render(
      <ReferenceLineTooltipContent
        active={false}
        payload={samplePayload}
        buckets={sampleBuckets}
        userAchievementCounts={{ softcore: 15, hardcore: 12 }}
        variant="game"
      />,
    );

    // ASSERT
    expect(screen.getByTestId('base-tooltip')).toBeVisible();
    expect(screen.queryByText(/your hardcore progress/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/your softcore progress/i)).not.toBeInTheDocument();
  });

  it('given payload is empty, renders the base tooltip content', () => {
    // ARRANGE
    render(
      <ReferenceLineTooltipContent
        active={true}
        payload={[]}
        buckets={sampleBuckets}
        userAchievementCounts={{ softcore: 15, hardcore: 12 }}
        variant="game"
      />,
    );

    // ASSERT
    expect(screen.getByTestId('base-tooltip')).toBeVisible();
    expect(screen.queryByText(/your hardcore progress/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/your softcore progress/i)).not.toBeInTheDocument();
  });

  it('given the current bucket has no reference lines, renders the base tooltip content only', () => {
    // ARRANGE
    render(
      <ReferenceLineTooltipContent
        active={true}
        payload={samplePayload}
        buckets={sampleBuckets}
        userAchievementCounts={{ softcore: 15, hardcore: 12 }}
        variant="game"
        userHardcoreIndex={0} // !! Different from current payload index (1)
        userSoftcoreIndex={2} // !! Different from current payload index (1)
      />,
    );

    // ASSERT
    expect(screen.getByTestId('base-tooltip')).toBeVisible();
    expect(screen.queryByText(/your hardcore progress/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/your softcore progress/i)).not.toBeInTheDocument();
  });

  it('given the current bucket has a hardcore reference line, renders hardcore line info', () => {
    // ARRANGE
    render(
      <ReferenceLineTooltipContent
        active={true}
        payload={samplePayload}
        buckets={sampleBuckets}
        userAchievementCounts={{ softcore: 15, hardcore: 12 }}
        userHardcoreIndex={1} // !! Same as current payload index (1)
        variant="game"
      />,
    );

    // ASSERT
    expect(screen.getByTestId('base-tooltip')).toBeVisible();
    expect(screen.getByText(/your hardcore progress/i)).toBeVisible();
    expect(screen.queryByText(/your softcore progress/i)).not.toBeInTheDocument();
  });

  it('given the current bucket has a softcore reference line, renders softcore line info', () => {
    // ARRANGE
    render(
      <ReferenceLineTooltipContent
        active={true}
        payload={samplePayload}
        buckets={sampleBuckets}
        userAchievementCounts={{ softcore: 15, hardcore: 12 }}
        userSoftcoreIndex={1} // !! Same as current payload index (1)
        variant="game"
      />,
    );

    // ASSERT
    expect(screen.getByTestId('base-tooltip')).toBeVisible();
    expect(screen.queryByText(/your hardcore progress/i)).not.toBeInTheDocument();
    expect(screen.getByText(/your softcore progress/i)).toBeVisible();
  });

  it('given the current bucket has both reference lines, renders both line info', () => {
    // ARRANGE
    render(
      <ReferenceLineTooltipContent
        active={true}
        payload={samplePayload}
        buckets={sampleBuckets}
        userAchievementCounts={{ softcore: 15, hardcore: 12 }}
        userHardcoreIndex={1} // !! Same as current payload index (1)
        userSoftcoreIndex={1} // !! Same as current payload index (1)
        variant="game"
      />,
    );

    // ASSERT
    expect(screen.getByTestId('base-tooltip')).toBeVisible();
    expect(screen.getByText(/your hardcore progress/i)).toBeVisible();
    expect(screen.getByText(/your softcore progress/i)).toBeVisible();
  });

  it('given user achievement counts are null for hardcore, still renders hardcore line info when index matches', () => {
    // ARRANGE
    render(
      <ReferenceLineTooltipContent
        active={true}
        payload={samplePayload}
        buckets={sampleBuckets}
        userAchievementCounts={{ softcore: 15, hardcore: null }}
        userHardcoreIndex={1} // !! Same as current payload index (1)
        variant="game"
      />,
    );

    // ASSERT
    expect(screen.getByTestId('base-tooltip')).toBeVisible();
    expect(screen.getByText(/your hardcore progress/i)).toBeVisible();
  });

  it('given user achievement counts are null for softcore, still renders softcore line info when index matches', () => {
    // ARRANGE
    render(
      <ReferenceLineTooltipContent
        active={true}
        payload={samplePayload}
        buckets={sampleBuckets}
        userAchievementCounts={{ softcore: null, hardcore: 12 }}
        userSoftcoreIndex={1} // !! Same as current payload index (1)
        variant="game"
      />,
    );

    // ASSERT
    expect(screen.getByTestId('base-tooltip')).toBeVisible();
    expect(screen.getByText(/your softcore progress/i)).toBeVisible();
  });

  it("given userAchievementCounts is null, doesn't render any reference line info", () => {
    // ARRANGE
    render(
      <ReferenceLineTooltipContent
        active={true}
        payload={samplePayload}
        buckets={sampleBuckets}
        userAchievementCounts={null}
        userHardcoreIndex={1}
        userSoftcoreIndex={1}
        variant="game"
      />,
    );

    // ASSERT
    expect(screen.getByTestId('base-tooltip')).toBeVisible();
    expect(screen.queryByText(/your hardcore progress/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/your softcore progress/i)).not.toBeInTheDocument();
  });

  it('given the variant is event, does not delineate between softcore and hardcore', () => {
    // ARRANGE
    render(
      <ReferenceLineTooltipContent
        active={true}
        payload={samplePayload}
        buckets={sampleBuckets}
        userAchievementCounts={{ softcore: 15, hardcore: 12 }}
        userHardcoreIndex={1} // !! Same as current payload index (1)
        variant="event" // !!
      />,
    );

    // ASSERT
    expect(screen.getByTestId('base-tooltip')).toBeVisible();

    expect(screen.queryByText(/your hardcore progress/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/your softcore progress/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/hardcore players/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/softcore players/i)).not.toBeInTheDocument();

    expect(screen.getByText(/your progress/i)).toBeVisible();
  });
});
