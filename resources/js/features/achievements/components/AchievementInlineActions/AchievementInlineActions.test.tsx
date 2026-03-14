import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createAchievement } from '@/test/factories';

import { ResetProgressDialog } from '../ResetProgressDialog';
import { UpdatePromotedStatusDialog } from '../UpdatePromotedStatusDialog';
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

  it('given the user clicks the reset progress button, opens the reset progress dialog', async () => {
    // ARRANGE
    const achievement = createAchievement({ unlockedAt: '2024-01-15T12:00:00Z' });

    render(
      <>
        <AchievementInlineActions />
        <ResetProgressDialog />
      </>,
      { pageProps: { achievement } },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /reset progress/i }));

    // ASSERT
    expect(screen.getByRole('dialog')).toBeVisible();
    expect(screen.getByRole('heading', { name: /reset progress/i })).toBeVisible();
  });

  it('given the user can develop and is not in edit mode, shows the Manage link and Quick edit button', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 789 });

    render(<AchievementInlineActions />, {
      pageProps: {
        achievement,
        can: { develop: true, viewAchievementLogic: false },
      },
    });

    // ASSERT
    const manageLink = screen.getByRole('link', { name: /manage/i });
    expect(manageLink).toBeVisible();
    expect(manageLink).toHaveAttribute('href', '/manage/achievements/789');

    expect(screen.getByRole('button', { name: /quick edit/i })).toBeVisible();
  });

  it('given the user can view achievement logic, shows the Logic link', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 789 });

    render(<AchievementInlineActions />, {
      pageProps: {
        achievement,
        can: { develop: true, viewAchievementLogic: true },
      },
    });

    // ASSERT
    const logicLink = screen.getByRole('link', { name: /logic/i });
    expect(logicLink).toBeVisible();
    expect(logicLink).toHaveAttribute('href', '/manage/achievements/789/logic');
  });

  it('given the user cannot view achievement logic, does not show the Logic link', () => {
    // ARRANGE
    const achievement = createAchievement();

    render(<AchievementInlineActions />, {
      pageProps: {
        achievement,
        can: { develop: true, viewAchievementLogic: false },
      },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /logic/i })).not.toBeInTheDocument();
  });

  it('given the user clicks Quick Edit, shows Cancel and Save buttons in its place', async () => {
    // ARRANGE
    const achievement = createAchievement();

    render(<AchievementInlineActions />, {
      pageProps: {
        achievement,
        can: { develop: true, viewAchievementLogic: false },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /quick edit/i }));

    // ASSERT
    expect(screen.getByRole('button', { name: /cancel/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /save/i })).toBeVisible();
    expect(screen.queryByRole('button', { name: /quick edit/i })).not.toBeInTheDocument();
  });

  it('given the user is in edit mode and can update the promoted status on an unpromoted achievement, shows the Promote button', async () => {
    // ARRANGE
    const achievement = createAchievement({ isPromoted: false });

    render(<AchievementInlineActions />, {
      pageProps: {
        achievement,
        can: { develop: true, updateAchievementIsPromoted: true, viewAchievementLogic: false },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /quick edit/i }));

    // ASSERT
    expect(screen.getByRole('button', { name: /promote/i })).toBeVisible();
  });

  it('given the user is in edit mode and can update the promoted status on a promoted achievement, shows the Demote button', async () => {
    // ARRANGE
    const achievement = createAchievement({ isPromoted: true });

    render(<AchievementInlineActions />, {
      pageProps: {
        achievement,
        can: { develop: true, updateAchievementIsPromoted: true, viewAchievementLogic: false },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /quick edit/i }));

    // ASSERT
    expect(screen.getByRole('button', { name: /demote/i })).toBeVisible();
  });

  it('given the user clicks the Promote button, opens the promote/demote dialog', async () => {
    // ARRANGE
    const achievement = createAchievement({ isPromoted: false });

    render(
      <>
        <AchievementInlineActions />
        <UpdatePromotedStatusDialog />
      </>,
      {
        pageProps: {
          achievement,
          can: { develop: true, updateAchievementIsPromoted: true, viewAchievementLogic: false },
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /quick edit/i }));
    await userEvent.click(screen.getByRole('button', { name: /promote/i }));

    // ASSERT
    expect(screen.getByText(/are you sure you want to promote this achievement/i)).toBeVisible();
  });

  it('given the achievement is for an event game, does not show the report issue link or ticket count', () => {
    // ARRANGE
    const achievement = createAchievement({ numUnresolvedTickets: 3 });
    render(<AchievementInlineActions />, {
      pageProps: { achievement, isEventGame: true },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /report an issue/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('link', { name: /3/i })).not.toBeInTheDocument();
  });

  it('given the achievement is for an event game and the user has unlocked it, does not show the reset progress button', () => {
    // ARRANGE
    const achievement = createAchievement({ unlockedAt: '2024-01-15T12:00:00Z' });
    render(<AchievementInlineActions />, {
      pageProps: { achievement, isEventGame: true },
    });

    // ASSERT
    expect(screen.queryByRole('button', { name: /reset progress/i })).not.toBeInTheDocument();
  });

  it('given the achievement is for an event game and the user can develop, does not show Quick Edit or Logic', () => {
    // ARRANGE
    const achievement = createAchievement();
    render(<AchievementInlineActions />, {
      pageProps: {
        achievement,
        can: { develop: true, viewAchievementLogic: true },
        isEventGame: true,
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /manage/i })).toBeVisible();
    expect(screen.queryByRole('button', { name: /quick edit/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('link', { name: /logic/i })).not.toBeInTheDocument();
  });

  it('given the user cannot update the promoted status, does not show the Promote or Demote button in edit mode', async () => {
    // ARRANGE
    const achievement = createAchievement({ isPromoted: false });

    render(<AchievementInlineActions />, {
      pageProps: {
        achievement,
        can: { develop: true, updateAchievementIsPromoted: false, viewAchievementLogic: false },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /quick edit/i }));

    // ASSERT
    expect(screen.queryByRole('button', { name: /promote/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /demote/i })).not.toBeInTheDocument();
  });
});
