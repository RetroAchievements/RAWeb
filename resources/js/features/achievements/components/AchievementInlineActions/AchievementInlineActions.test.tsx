import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createAchievement, createEventAchievement } from '@/test/factories';

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
      pageProps: { achievement, can: { createTicket: true } },
    });

    // ASSERT
    const reportLink = screen.getByRole('link', { name: /report an issue/i });

    expect(reportLink).toBeVisible();
    expect(reportLink).toHaveAttribute('href', expect.stringContaining('achievement.report-issue'));
  });

  it('given the user cannot create tickets, does not show the report an issue link', () => {
    // ARRANGE
    const achievement = createAchievement();
    render(<AchievementInlineActions />, {
      pageProps: { achievement, can: { createTicket: false } },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /report an issue/i })).not.toBeInTheDocument();
  });

  it('given the achievement has unresolved tickets, shows the open ticket count as a link', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 123, numUnresolvedTickets: 3 });
    render(<AchievementInlineActions />, {
      pageProps: { achievement, can: { createTicket: true } },
    });

    // ASSERT
    const ticketLink = screen.getByRole('link', { name: /3/i });

    expect(ticketLink).toBeVisible();
    expect(ticketLink).toHaveAttribute('href', expect.stringContaining('achievement.tickets'));
  });

  it('given the achievement has no unresolved tickets, does not show any ticket text', () => {
    // ARRANGE
    const achievement = createAchievement({ numUnresolvedTickets: 0 });
    render(<AchievementInlineActions />, {
      pageProps: { achievement, can: { createTicket: true } },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /report an issue/i })).toBeVisible();
    expect(screen.queryByRole('link', { name: /ticket/i })).not.toBeInTheDocument();
  });

  it('given the user has unlocked the achievement in softcore, shows the overflow menu button', () => {
    // ARRANGE
    const achievement = createAchievement({ unlockedAt: '2024-01-15T12:00:00Z' });
    render(<AchievementInlineActions />, {
      pageProps: { achievement, can: { develop: false } },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: 'More actions' })).toBeVisible();
  });

  it('given the user has unlocked the achievement in hardcore, shows the overflow menu button', () => {
    // ARRANGE
    const achievement = createAchievement({ unlockedHardcoreAt: '2024-01-15T12:00:00Z' });
    render(<AchievementInlineActions />, {
      pageProps: { achievement, can: { develop: false } },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: 'More actions' })).toBeVisible();
  });

  it('given the user is not logged in, does not show the overflow menu button', () => {
    // ARRANGE
    const achievement = createAchievement();
    delete (achievement as any).unlockedAt;
    delete (achievement as any).unlockedHardcoreAt;

    render(<AchievementInlineActions />, {
      pageProps: { achievement },
    });

    // ASSERT
    expect(screen.queryByRole('button', { name: 'More actions' })).not.toBeInTheDocument();
  });

  it('given the user clicks the overflow menu and then reset progress, opens the reset progress dialog', async () => {
    // ARRANGE
    const achievement = createAchievement({ unlockedAt: '2024-01-15T12:00:00Z' });

    render(
      <>
        <AchievementInlineActions />
        <ResetProgressDialog />
      </>,
      { pageProps: { achievement, can: { develop: true } } },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'More actions' }));
    await userEvent.click(screen.getByRole('menuitem', { name: /reset progress/i }));

    // ASSERT
    expect(screen.getByRole('dialog')).toBeVisible();
    expect(screen.getByRole('heading', { name: /reset progress/i })).toBeVisible();
  });

  it('given the user clicks the desktop reset progress button, opens the reset progress dialog', async () => {
    // ARRANGE
    const achievement = createAchievement({ unlockedAt: '2024-01-15T12:00:00Z' });

    render(
      <>
        <AchievementInlineActions />
        <ResetProgressDialog />
      </>,
      { pageProps: { achievement, can: { develop: false } } },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Reset progress' }));

    // ASSERT
    expect(screen.getByRole('dialog')).toBeVisible();
    expect(screen.getByRole('heading', { name: /reset progress/i })).toBeVisible();
  });

  it('given the user can develop and has unlocked the achievement, shows a separator in the dropdown', async () => {
    // ARRANGE
    const achievement = createAchievement({ unlockedAt: '2024-01-15T12:00:00Z' });

    render(<AchievementInlineActions />, {
      pageProps: {
        achievement,
        can: {
          develop: true,
          manageAchievements: true,
          quickEditAchievement: true,
          viewAchievementLogic: false,
        },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'More actions' }));

    // ASSERT
    expect(screen.getByRole('separator')).toBeVisible();
  });

  it('given the user can develop and update description, shows the Manage and Quick edit items in the dropdown', async () => {
    // ARRANGE
    const achievement = createAchievement({ id: 789 });

    render(<AchievementInlineActions />, {
      pageProps: {
        achievement,
        can: {
          develop: true,
          manageAchievements: true,
          quickEditAchievement: true,
          viewAchievementLogic: false,
        },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'More actions' }));

    // ASSERT
    const manageItem = screen.getByRole('menuitem', { name: /manage/i });
    expect(manageItem).toBeVisible();
    expect(manageItem).toHaveAttribute('href', '/manage/achievements/789');

    expect(screen.getByRole('menuitem', { name: /quick edit/i })).toBeVisible();
  });

  it('given the user can develop but cannot update title or description, shows Manage but not Quick edit items in the dropdown', async () => {
    // ARRANGE
    const achievement = createAchievement({ id: 789 });

    render(<AchievementInlineActions />, {
      pageProps: {
        achievement,
        can: {
          develop: true,
          manageAchievements: true,
          quickEditAchievement: false,
          viewAchievementLogic: false,
        },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'More actions' }));

    // ASSERT
    expect(screen.getByRole('menuitem', { name: /manage/i })).toBeVisible();
    expect(screen.queryByRole('menuitem', { name: /quick edit/i })).not.toBeInTheDocument();
  });

  it('given the user can view achievement logic, shows the Logic item in the dropdown', async () => {
    // ARRANGE
    const achievement = createAchievement({ id: 789 });

    render(<AchievementInlineActions />, {
      pageProps: {
        achievement,
        can: { develop: true, manageAchievements: true, viewAchievementLogic: true },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'More actions' }));

    // ASSERT
    const logicItem = screen.getByRole('menuitem', { name: /logic/i });
    expect(logicItem).toBeVisible();
    expect(logicItem).toHaveAttribute('href', '/manage/achievements/789/logic');
  });

  it('given the user cannot view achievement logic, does not show the Logic item in the dropdown', async () => {
    // ARRANGE
    const achievement = createAchievement();

    render(<AchievementInlineActions />, {
      pageProps: {
        achievement,
        can: {
          develop: true,
          manageAchievements: true,
          quickEditAchievement: true,
          viewAchievementLogic: false,
        },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'More actions' }));

    // ASSERT
    expect(screen.queryByRole('menuitem', { name: /logic/i })).not.toBeInTheDocument();
  });

  it('given the user clicks Quick Edit in the dropdown, shows Cancel and Save buttons', async () => {
    // ARRANGE
    const achievement = createAchievement();

    render(<AchievementInlineActions />, {
      pageProps: {
        achievement,
        can: {
          develop: true,
          manageAchievements: true,
          quickEditAchievement: true,
          viewAchievementLogic: false,
        },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'More actions' }));
    await userEvent.click(screen.getByRole('menuitem', { name: /quick edit/i }));

    // ASSERT
    expect(screen.getByRole('button', { name: /cancel/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /save/i })).toBeVisible();
    expect(screen.queryByRole('button', { name: 'More actions' })).not.toBeInTheDocument();
  });

  it('given the user is in edit mode and can update the promoted status on an unpromoted achievement, shows the Promote button', async () => {
    // ARRANGE
    const achievement = createAchievement({ isPromoted: false });

    render(<AchievementInlineActions />, {
      pageProps: {
        achievement,
        can: {
          develop: true,
          manageAchievements: true,
          quickEditAchievement: true,
          updateAchievementIsPromoted: true,
          viewAchievementLogic: false,
        },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'More actions' }));
    await userEvent.click(screen.getByRole('menuitem', { name: /quick edit/i }));

    // ASSERT
    expect(screen.getByRole('button', { name: /promote/i })).toBeVisible();
  });

  it('given the user is in edit mode and can update the promoted status on a promoted achievement, shows the Demote button', async () => {
    // ARRANGE
    const achievement = createAchievement({ isPromoted: true });

    render(<AchievementInlineActions />, {
      pageProps: {
        achievement,
        can: {
          develop: true,
          manageAchievements: true,
          quickEditAchievement: true,
          updateAchievementIsPromoted: true,
          viewAchievementLogic: false,
        },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'More actions' }));
    await userEvent.click(screen.getByRole('menuitem', { name: /quick edit/i }));

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
          can: {
            develop: true,
            manageAchievements: true,
            quickEditAchievement: true,
            updateAchievementIsPromoted: true,
            viewAchievementLogic: false,
          },
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'More actions' }));
    await userEvent.click(screen.getByRole('menuitem', { name: /quick edit/i }));
    await userEvent.click(screen.getByRole('button', { name: /promote/i }));

    // ASSERT
    expect(screen.getByText(/are you sure you want to promote this achievement/i)).toBeVisible();
  });

  it('given a revealed event source achievement, shows report issue and tickets from that source achievement', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 100, numUnresolvedTickets: 0 });

    render(<AchievementInlineActions />, {
      pageProps: {
        achievement,
        can: { createTicket: true },
        isEventGame: true,
        eventAchievement: createEventAchievement({
          sourceAchievement: createAchievement({ id: 555, numUnresolvedTickets: 2 }),
        }),
      },
    });

    // ASSERT
    expect(screen.getByText(/report an issue/i)).toBeVisible();
    expect(screen.getByText(/2 open tickets/i)).toBeVisible();
  });

  it('given an obfuscated event achievement, does not show report issue', () => {
    // ARRANGE
    const achievement = createAchievement({ numUnresolvedTickets: 3 });

    render(<AchievementInlineActions />, {
      pageProps: {
        achievement,
        isEventGame: true,
        eventAchievement: createEventAchievement({
          isObfuscated: true,
          sourceAchievement: null,
        }),
      },
    });

    // ASSERT
    expect(screen.queryByText(/report an issue/i)).not.toBeInTheDocument();
  });

  it('given the achievement is for an event game and the user has unlocked it, does not show the reset progress button', () => {
    // ARRANGE
    const achievement = createAchievement({ unlockedAt: '2024-01-15T12:00:00Z' });
    render(<AchievementInlineActions />, {
      pageProps: { achievement, isEventGame: true, can: { develop: false } },
    });

    // ASSERT
    expect(screen.queryByRole('button', { name: 'More actions' })).not.toBeInTheDocument();
  });

  it('given the achievement is for an event game and the user can develop, does not show Quick Edit or Logic', async () => {
    // ARRANGE
    const achievement = createAchievement();
    render(<AchievementInlineActions />, {
      pageProps: {
        achievement,
        can: { develop: true, manageAchievements: true, viewAchievementLogic: true },
        isEventGame: true,
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'More actions' }));

    // ASSERT
    expect(screen.getByRole('menuitem', { name: /manage/i })).toBeVisible();
    expect(screen.queryByRole('menuitem', { name: /quick edit/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('menuitem', { name: /logic/i })).not.toBeInTheDocument();
  });

  it('given the user cannot update the promoted status, does not show the Promote or Demote button in edit mode', async () => {
    // ARRANGE
    const achievement = createAchievement({ isPromoted: false });

    render(<AchievementInlineActions />, {
      pageProps: {
        achievement,
        can: {
          develop: true,
          manageAchievements: true,
          quickEditAchievement: true,
          updateAchievementIsPromoted: false,
          viewAchievementLogic: false,
        },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'More actions' }));
    await userEvent.click(screen.getByRole('menuitem', { name: /quick edit/i }));

    // ASSERT
    expect(screen.queryByRole('button', { name: /promote/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: /demote/i })).not.toBeInTheDocument();
  });
});
