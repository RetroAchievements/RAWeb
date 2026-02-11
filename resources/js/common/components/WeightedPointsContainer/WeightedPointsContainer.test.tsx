import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';

import { WeightedPointsContainer } from './WeightedPointsContainer';

describe('Component: WeightedPointsContainer', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<WeightedPointsContainer>100</WeightedPointsContainer>);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders children', () => {
    // ARRANGE
    render(<WeightedPointsContainer>100</WeightedPointsContainer>);

    // ASSERT
    expect(screen.getByText('100')).toBeVisible();
  });

  it('given the user hovers, shows a tooltip', async () => {
    // ARRANGE
    render(<WeightedPointsContainer>100</WeightedPointsContainer>);

    // ACT
    await userEvent.hover(screen.getByText('100'));

    // ASSERT
    expect(await screen.findByRole('tooltip')).toBeVisible();
    expect((await screen.findAllByText(/rarer achievements/i)).length).toBeGreaterThanOrEqual(1);
    expect(screen.queryAllByText(/adjusted by achievement rarity/i).length).toBe(0);
  });

  it('given the user hovers, shows a verbose tooltip', async () => {
    // ARRANGE
    render(<WeightedPointsContainer useVerboseTooltip={true}>100</WeightedPointsContainer>);

    // ACT
    await userEvent.hover(screen.getByText('100'));

    // ASSERT
    expect(await screen.findByRole('tooltip')).toBeVisible();
    expect((await screen.findAllByText(/rarer achievements/i)).length).toBeGreaterThanOrEqual(1);
    expect(
      (await screen.findAllByText(/adjusted by achievement rarity/i)).length,
    ).toBeGreaterThanOrEqual(1);
  });

  it('can be configured to not have a tooltip', async () => {
    // ARRANGE
    render(<WeightedPointsContainer isTooltipEnabled={false}>100</WeightedPointsContainer>);

    // ACT
    await userEvent.hover(screen.getByText('100'));

    // ASSERT
    await waitFor(() => {
      expect(screen.queryByRole('tooltip')).not.toBeInTheDocument();
    });
  });
});
