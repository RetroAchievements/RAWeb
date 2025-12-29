import { render, screen } from '@/test';
import { createPlayerBadge } from '@/test/factories';

import { PlayerBadgeIndicator } from './PlayerBadgeIndicator';

describe('Component: PlayerBadgeIndicator', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<PlayerBadgeIndicator playerBadge={createPlayerBadge()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders an accessible label for mastery', () => {
    // ARRANGE
    const playerBadge = createPlayerBadge({
      awardType: 'mastery',
      awardTier: 1,
    });

    render(<PlayerBadgeIndicator playerBadge={playerBadge} />);

    // ASSERT
    expect(screen.getByLabelText(/mastered/i)).toBeVisible();
  });

  it('renders an accessible label for completion', () => {
    // ARRANGE
    const playerBadge = createPlayerBadge({
      awardType: 'mastery',
      awardTier: 0,
    });

    render(<PlayerBadgeIndicator playerBadge={playerBadge} />);

    // ASSERT
    expect(screen.getByLabelText(/completed/i)).toBeVisible();
  });

  it('renders an accessible label for beaten', () => {
    // ARRANGE
    const playerBadge = createPlayerBadge({
      awardType: 'game_beaten',
      awardTier: 1,
    });

    render(<PlayerBadgeIndicator playerBadge={playerBadge} />);

    // ASSERT
    expect(screen.getByLabelText(/beaten/i)).toBeVisible();
  });

  it('renders an accessible label for beaten softcore', () => {
    // ARRANGE
    const playerBadge = createPlayerBadge({
      awardType: 'game_beaten',
      awardTier: 0,
    });

    render(<PlayerBadgeIndicator playerBadge={playerBadge} />);

    // ASSERT
    expect(screen.getByLabelText(/beaten \(softcore\)/i)).toBeVisible();
  });
});
