import { render, screen } from '@/test';
import { createAchievement, createEventAward, createRaEvent } from '@/test/factories';

import { AwardTierItem } from './AwardTierItem';

describe('Component: AwardTierItem', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const event = createRaEvent();
    const eventAward = createEventAward();

    // ASSERT
    expect(() => render(<AwardTierItem event={event} eventAward={eventAward} />)).not.toThrow();
  });

  it('given an unearned award, shows the badge with reduced opacity', () => {
    // ARRANGE
    const event = createRaEvent();
    const eventAward = createEventAward({ earnedAt: null });

    // ACT
    render(<AwardTierItem event={event} eventAward={eventAward} />);

    // ASSERT
    const badge = screen.getByRole('img');
    expect(badge).toHaveClass('opacity-50');
  });

  it('given an earned award, shows the badge with full opacity and gold outline', () => {
    // ARRANGE
    const event = createRaEvent();
    const eventAward = createEventAward({ earnedAt: '2023-01-01' });

    // ACT
    render(<AwardTierItem event={event} eventAward={eventAward} />);

    // ASSERT
    const badge = screen.getByRole('img');
    expect(badge).toHaveClass('opacity-100');
    expect(badge).toHaveClass('outline-[gold]');
  });

  it('given an earned award, shows a checkmark', () => {
    // ARRANGE
    const event = createRaEvent();
    const eventAward = createEventAward({ earnedAt: '2023-01-01' });

    // ACT
    render(<AwardTierItem event={event} eventAward={eventAward} />);

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

    // ACT
    render(<AwardTierItem event={event} eventAward={eventAward} />);

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

    // ACT
    render(<AwardTierItem event={event} eventAward={eventAward} />);

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

    // ACT
    render(<AwardTierItem event={event} eventAward={eventAward} />);

    // ASSERT
    expect(screen.getByText(/earned by you and 4 other players/i)).toBeVisible();
  });

  it('given all achievements are worth one point, shows achievement count instead of points', () => {
    // ARRANGE
    const event = createRaEvent({
      eventAchievements: [
        { achievement: createAchievement({ points: 1 }) },
        { achievement: createAchievement({ points: 1 }) },
      ],
    });
    const eventAward = createEventAward({ pointsRequired: 2 });

    // ACT
    render(<AwardTierItem event={event} eventAward={eventAward} />);

    // ASSERT
    expect(screen.getByText(/2 achievements/i)).toBeVisible();
    expect(screen.queryByText(/points/i)).not.toBeInTheDocument();
  });

  it('given achievements have varying points, shows points required', () => {
    // ARRANGE
    const event = createRaEvent({
      eventAchievements: [
        { achievement: createAchievement({ points: 1 }) },
        { achievement: createAchievement({ points: 2 }) },
      ],
    });
    const eventAward = createEventAward({ pointsRequired: 3 });

    // ACT
    render(<AwardTierItem event={event} eventAward={eventAward} />);

    // ASSERT
    expect(screen.getByText(/3 points/i)).toBeVisible();
  });
});
