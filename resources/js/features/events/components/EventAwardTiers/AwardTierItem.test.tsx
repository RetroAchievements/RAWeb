import { render, screen } from '@/test';
import { createAchievement, createEventAward, createRaEvent } from '@/test/factories';

import { AwardTierItem } from './AwardTierItem';

describe('Component: AwardTierItem', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const event = createRaEvent();
    const eventAward = createEventAward();

    const { container } = render(
      <AwardTierItem event={event} eventAward={eventAward} hasVirtualTier={false} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given an unearned award, shows the badge with reduced opacity', () => {
    // ARRANGE
    const event = createRaEvent();
    const eventAward = createEventAward({ earnedAt: null });

    render(<AwardTierItem event={event} eventAward={eventAward} hasVirtualTier={false} />);

    // ASSERT
    const badge = screen.getByRole('img');
    expect(badge).toHaveClass('opacity-50');
  });

  it('given an earned award, shows the badge with full opacity and gold outline', () => {
    // ARRANGE
    const event = createRaEvent();
    const eventAward = createEventAward({ label: 'Gold', earnedAt: '2023-01-01' });

    render(<AwardTierItem event={event} eventAward={eventAward} hasVirtualTier={false} />);

    // ASSERT
    const badge = screen.getByRole('img');
    expect(badge).toHaveClass('opacity-100');
    expect(badge).toHaveClass('outline-[gold]');

    expect(screen.getByTestId('award-tier-label')).toBeVisible();
  });

  it('given an earned award, shows a checkmark', () => {
    // ARRANGE
    const event = createRaEvent();
    const eventAward = createEventAward({ earnedAt: '2023-01-01' });

    render(<AwardTierItem event={event} eventAward={eventAward} hasVirtualTier={false} />);

    // ASSERT
    const checkmark = screen.getByTestId('award-earned-checkmark');
    expect(checkmark).toBeVisible();
  });

  it('given an award with one earner, shows the correct earners message', () => {
    // ARRANGE
    const event = createRaEvent();
    const eventAward = createEventAward({
      earnedAt: '2023-01-01',
      badgeCount: 1,
    });

    render(<AwardTierItem event={event} eventAward={eventAward} hasVirtualTier={false} />);

    // ASSERT
    expect(screen.getByText(/you are the only player to earn this/i)).toBeVisible();
  });

  it('given an unearthed award with multiple earners, shows the correct earners message', () => {
    // ARRANGE
    const event = createRaEvent();
    const eventAward = createEventAward({
      earnedAt: null,
      badgeCount: 5,
    });

    render(<AwardTierItem event={event} eventAward={eventAward} hasVirtualTier={false} />);

    // ASSERT
    expect(screen.getByText(/5 players have earned this/i)).toBeVisible();
  });

  it('given an earned award with multiple earners, shows the correct earners message', () => {
    // ARRANGE
    const event = createRaEvent();
    const eventAward = createEventAward({
      earnedAt: '2023-01-01',
      badgeCount: 5,
    });

    render(<AwardTierItem event={event} eventAward={eventAward} hasVirtualTier={false} />);

    // ASSERT
    expect(screen.getByText(/earned by you and 4 other players/i)).toBeVisible();
  });

  it('given all achievements are worth one point, shows achievement count instead of points', () => {
    // ARRANGE
    const event = createRaEvent({
      eventAchievements: [
        { achievement: createAchievement({ points: 1 }), isObfuscated: false },
        { achievement: createAchievement({ points: 1 }), isObfuscated: false },
      ],
    });
    const eventAward = createEventAward({ pointsRequired: 2 });

    render(<AwardTierItem event={event} eventAward={eventAward} hasVirtualTier={false} />);

    // ASSERT
    expect(screen.getByText(/2 achievements/i)).toBeVisible();
    expect(screen.queryByText(/points/i)).not.toBeInTheDocument();
  });

  it('given achievements have varying points, shows points required', () => {
    // ARRANGE
    const event = createRaEvent({
      eventAchievements: [
        { achievement: createAchievement({ points: 1 }), isObfuscated: false },
        { achievement: createAchievement({ points: 2 }), isObfuscated: false },
      ],
    });
    const eventAward = createEventAward({ pointsRequired: 3 });

    render(<AwardTierItem event={event} eventAward={eventAward} hasVirtualTier={false} />);

    // ASSERT
    expect(screen.getByText(/3 points/i)).toBeVisible();
  });

  it('given hasVirtualTier is truthy, does not display the award tier label', () => {
    // ARRANGE
    const event = createRaEvent({
      eventAchievements: [
        { achievement: createAchievement({ points: 1 }), isObfuscated: false },
        { achievement: createAchievement({ points: 2 }), isObfuscated: false },
      ],
    });
    const eventAward = createEventAward({ pointsRequired: 3 });

    render(<AwardTierItem event={event} eventAward={eventAward} hasVirtualTier={true} />);

    // ASSERT
    expect(screen.queryByTestId('award-tier-label')).not.toBeInTheDocument();
  });

  it('given an earn count, shows a link to earners', () => {
    // ARRANGE
    const event = createRaEvent();
    const eventAward = createEventAward({ badgeCount: 10, label: 'Bronze' });

    render(<AwardTierItem event={event} eventAward={eventAward} hasVirtualTier={false} />);

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /bronze/i });
    expect(linkEl).toBeVisible();
  });

  it('given no earn count, does not show a link to earners', () => {
    // ARRANGE
    const event = createRaEvent();
    const eventAward = createEventAward({ badgeCount: 0, label: 'Bronze' });

    render(<AwardTierItem event={event} eventAward={eventAward} hasVirtualTier={false} />);

    // ASSERT
    const linkEl = screen.queryByRole('link', { name: /bronze/i });
    expect(linkEl).not.toBeInTheDocument();
  });
});
