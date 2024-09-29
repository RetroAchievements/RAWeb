import { AwardType } from '@/common/utils/generatedAppConstants';
import { render, screen } from '@/test';
import { createPlayerBadge } from '@/test/factories';

import { PlayerBadgeIndicator } from './PlayerBadgeIndicator';

describe('Component: PlayerBadgeIndicator', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<PlayerBadgeIndicator {...createPlayerBadge()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders an accessible label for mastery', () => {
    // ARRANGE
    const playerBadge = createPlayerBadge({
      awardType: AwardType.Mastery,
      awardDataExtra: 1,
    });

    render(<PlayerBadgeIndicator {...playerBadge} />);

    // ASSERT
    expect(screen.getByLabelText(/mastered/i)).toBeVisible();
  });

  it('renders an accessible label for completion', () => {
    // ARRANGE
    const playerBadge = createPlayerBadge({
      awardType: AwardType.Mastery,
      awardDataExtra: 0,
    });

    render(<PlayerBadgeIndicator {...playerBadge} />);

    // ASSERT
    expect(screen.getByLabelText(/completed/i)).toBeVisible();
  });

  it('renders an accessible label for beaten', () => {
    // ARRANGE
    const playerBadge = createPlayerBadge({
      awardType: AwardType.GameBeaten,
      awardDataExtra: 1,
    });

    render(<PlayerBadgeIndicator {...playerBadge} />);

    // ASSERT
    expect(screen.getByLabelText(/beaten/i)).toBeVisible();
  });

  it('renders an accessible label for beaten softcore', () => {
    // ARRANGE
    const playerBadge = createPlayerBadge({
      awardType: AwardType.GameBeaten,
      awardDataExtra: 0,
    });

    render(<PlayerBadgeIndicator {...playerBadge} />);

    // ASSERT
    expect(screen.getByLabelText(/beaten \(softcore\)/i)).toBeVisible();
  });
});
