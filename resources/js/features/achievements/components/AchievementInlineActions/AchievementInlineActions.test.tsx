import { render, screen } from '@/test';
import { createAchievement } from '@/test/factories';

import { AchievementInlineActions } from './AchievementInlineActions';

describe('Component: AchievementInlineActions', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const achievement = createAchievement();
    const { container } = render(<AchievementInlineActions />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays a link to report an issue', () => {
    // ARRANGE
    const achievement = createAchievement();
    render(<AchievementInlineActions />, {
      pageProps: { achievement },
    });

    // ASSERT
    const reportLink = screen.getByRole('link', { name: /report an issue/i });

    expect(reportLink).toBeVisible();
    expect(reportLink).toHaveAttribute('href', expect.stringContaining('achievement.report-issue'));
  });

  it('given the achievement has unresolved tickets, shows the open ticket count as a link', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 123, numUnresolvedTickets: 3 });
    render(<AchievementInlineActions />, {
      pageProps: { achievement },
    });

    // ASSERT
    const ticketLink = screen.getByRole('link', { name: /3/i });

    expect(ticketLink).toBeVisible();
    expect(ticketLink).toHaveAttribute('href', expect.stringContaining('achievement.tickets'));
  });

  it('given the achievement has no unresolved tickets, shows a "No open tickets" message', () => {
    // ARRANGE
    const achievement = createAchievement({ numUnresolvedTickets: 0 });
    render(<AchievementInlineActions />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getByText(/no open tickets/i)).toBeVisible();
    expect(screen.queryByRole('link', { name: /ticket/i })).not.toBeInTheDocument();
  });

  it('given the achievement has no numUnresolvedTickets field, shows a "No open tickets" message', () => {
    // ARRANGE
    const achievement = createAchievement();
    delete (achievement as any).numUnresolvedTickets;

    render(<AchievementInlineActions />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getByText(/no open tickets/i)).toBeVisible();
  });

  it('given the user has unlocked the achievement in softcore, shows a reset progress button', () => {
    // ARRANGE
    const achievement = createAchievement({ unlockedAt: '2024-01-15T12:00:00Z' });
    render(<AchievementInlineActions />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /reset progress/i })).toBeVisible();
  });

  it('given the user has unlocked the achievement in hardcore, shows a reset progress button', () => {
    // ARRANGE
    const achievement = createAchievement({ unlockedHardcoreAt: '2024-01-15T12:00:00Z' });
    render(<AchievementInlineActions />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /reset progress/i })).toBeVisible();
  });

  it('given the user has not unlocked the achievement, does not show a reset progress button', () => {
    // ARRANGE
    const achievement = createAchievement();
    delete (achievement as any).unlockedAt;
    delete (achievement as any).unlockedHardcoreAt;

    render(<AchievementInlineActions />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.queryByRole('button', { name: /reset progress/i })).not.toBeInTheDocument();
  });
});
