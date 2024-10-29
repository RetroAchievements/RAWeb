import { AwardType } from '@/common/utils/generatedAppConstants';
import { render, screen } from '@/test';
import { createPlayerBadge } from '@/test/factories';

import { PlayerBadgeLabel } from './PlayerBadgeLabel';

describe('Component: PlayerBadgeLabel', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<PlayerBadgeLabel playerBadge={createPlayerBadge()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders a label for the given badge', () => {
    // ARRANGE
    const playerBadge = createPlayerBadge({ awardType: AwardType.Mastery, awardDataExtra: 1 });

    render(<PlayerBadgeLabel playerBadge={playerBadge} />);

    // ASSERT
    expect(screen.getByText(/mastered/i)).toBeVisible();
  });

  it('by default, colorizes the label', () => {
    // ARRANGE
    const playerBadge = createPlayerBadge({ awardType: AwardType.Mastery, awardDataExtra: 1 });

    render(<PlayerBadgeLabel playerBadge={playerBadge} />);

    // ASSERT
    const labelEl = screen.getByText(/mastered/i);

    expect(labelEl).toHaveClass('text-[gold]');
  });

  it('can be configured to not colorize the label', () => {
    // ARRANGE
    const playerBadge = createPlayerBadge({ awardType: AwardType.Mastery, awardDataExtra: 1 });

    render(<PlayerBadgeLabel playerBadge={playerBadge} isColorized={false} />);

    // ASSERT
    const labelEl = screen.getByText(/mastered/i);

    expect(labelEl).not.toHaveClass('text-[gold]');
  });
});
