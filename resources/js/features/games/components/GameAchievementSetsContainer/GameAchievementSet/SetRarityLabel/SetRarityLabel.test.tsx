import { render, screen } from '@/test';

import { SetRarityLabel } from './SetRarityLabel';

describe('Component: SetRarityLabel', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<SetRarityLabel pointsTotal={100} pointsWeighted={150} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the rarity multiplier with two decimal places', () => {
    // ARRANGE
    render(<SetRarityLabel pointsTotal={100} pointsWeighted={350} />);

    // ASSERT
    expect(screen.getByText(/×3.50/i)).toBeVisible();
  });

  it('displays the gem icon', () => {
    // ARRANGE
    render(<SetRarityLabel pointsTotal={100} pointsWeighted={200} />);

    // ASSERT
    const gemIcon = screen.getByText(/×2.00/i).querySelector('svg');
    expect(gemIcon).toBeInTheDocument();
  });

  it('given zero points total, does not display a rarity label', () => {
    // ARRANGE
    render(<SetRarityLabel pointsTotal={0} pointsWeighted={0} />);

    // ASSERT
    expect(screen.queryByText(/×/i)).not.toBeInTheDocument();
  });

  it('given zero weighted points, displays nothing', () => {
    // ARRANGE
    render(<SetRarityLabel pointsTotal={100} pointsWeighted={0} />);

    // ASSERT
    expect(screen.queryByText(/×/i)).not.toBeInTheDocument();
  });

  it('rounds the rarity multiplier correctly', () => {
    // ARRANGE
    render(<SetRarityLabel pointsTotal={100} pointsWeighted={355} />);

    // ASSERT
    expect(screen.getByText(/×3.55/i)).toBeVisible();
  });

  it('handles decimal rarity values correctly', () => {
    // ARRANGE
    render(<SetRarityLabel pointsTotal={75} pointsWeighted={150} />);

    // ASSERT
    expect(screen.getByText(/×2.00/i)).toBeVisible();
  });
});
