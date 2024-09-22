import { AwardType } from '@/common/utils/generatedAppConstants';
import { render, screen } from '@/test';
import { createPlayerBadge } from '@/test/factories';

import { PlayerBadgeLabel } from './PlayerBadgeLabel';

describe('Component: PlayerBadgeLabel', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<PlayerBadgeLabel {...createPlayerBadge()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders a label for the given badge', () => {
    // ARRANGE
    const playerBadge = createPlayerBadge({ awardType: AwardType.Mastery, awardDataExtra: 1 });

    render(<PlayerBadgeLabel {...playerBadge} />);

    // ASSERT
    expect(screen.getByText(/mastered/i)).toBeVisible();
  });
});
