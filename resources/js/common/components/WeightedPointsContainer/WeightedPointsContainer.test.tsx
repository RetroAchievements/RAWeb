import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

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
    expect(
      (await screen.findAllByText(/rarity and estimated difficulty/i)).length,
    ).toBeGreaterThanOrEqual(1);
  });
});
