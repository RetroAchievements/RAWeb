import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import { createAchievement, createGame } from '@/test/factories';

import { AchievementContributePanel } from './AchievementContributePanel';

describe('Component: AchievementContributePanel', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    const { container } = render(<AchievementContributePanel />, {
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

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user cannot develop, renders nothing', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementContributePanel />, {
      pageProps: {
        achievement,
        can: { develop: false },
      },
    });

    // ASSERT
    expect(screen.queryByRole('button', { name: /quick edit/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('link', { name: /manage/i })).not.toBeInTheDocument();
  });

  it('given the user can develop, displays the Quick Edit button', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementContributePanel />, {
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

    // ASSERT
    expect(screen.getByRole('button', { name: /quick edit/i })).toBeVisible();
  });

  it('given the user can develop but cannot update title or description (e.g. Artist), does not display Quick Edit but displays Manage', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementContributePanel />, {
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

    // ASSERT
    expect(screen.queryByRole('button', { name: /quick edit/i })).not.toBeInTheDocument();
    expect(screen.getByRole('link', { name: /manage/i })).toBeVisible();
  });

  it('given the user can develop, displays the Manage link', () => {
    // ARRANGE
    const achievement = createAchievement({
      id: 123,
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementContributePanel />, {
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

    // ASSERT
    const manageLink = screen.getByRole('link', { name: /manage/i });
    expect(manageLink).toBeVisible();
    expect(manageLink).toHaveAttribute('href', '/manage/achievements/123');
  });

  it('given the user can view achievement logic, displays the Logic link', () => {
    // ARRANGE
    const achievement = createAchievement({
      id: 456,
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementContributePanel />, {
      pageProps: {
        achievement,
        can: {
          develop: true,
          manageAchievements: true,
          quickEditAchievement: true,
          viewAchievementLogic: true,
        },
      },
    });

    // ASSERT
    const logicLink = screen.getByRole('link', { name: /logic/i });
    expect(logicLink).toBeVisible();
    expect(logicLink).toHaveAttribute('href', '/manage/achievements/456/logic');
  });

  it('given the user cannot view achievement logic, does not display the Logic link', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementContributePanel />, {
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

    // ASSERT
    expect(screen.queryByRole('link', { name: /logic/i })).not.toBeInTheDocument();
  });

  it('given the user cannot manage achievements but can view logic, does not display the Manage link', () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementContributePanel />, {
      pageProps: {
        achievement,
        can: {
          develop: true,
          manageAchievements: false,
          quickEditAchievement: false,
          viewAchievementLogic: true,
        },
      },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /manage/i })).not.toBeInTheDocument();
    expect(screen.getByRole('link', { name: /logic/i })).toBeVisible();
  });

  it('given the user clicks Quick Edit, shows the Cancel Editing button', async () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementContributePanel />, {
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
    await userEvent.click(screen.getByRole('button', { name: /quick edit/i }));

    // ASSERT
    expect(screen.getByRole('button', { name: /cancel editing/i })).toBeVisible();
    expect(screen.queryByRole('button', { name: /quick edit/i })).not.toBeInTheDocument();
  });

  it('given the achievement is for an event game, only shows a full-width Manage button', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 789 });

    render(<AchievementContributePanel />, {
      pageProps: {
        achievement,
        can: {
          develop: true,
          manageAchievements: true,
          quickEditAchievement: true,
          viewAchievementLogic: true,
        },
        isEventGame: true,
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /manage/i })).toBeVisible();

    expect(screen.queryByRole('button', { name: /quick edit/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('link', { name: /logic/i })).not.toBeInTheDocument();
  });

  it('given the user clicks Cancel Editing, returns to showing the Quick Edit button', async () => {
    // ARRANGE
    const achievement = createAchievement({
      game: createGame({ playersTotal: 1000 }),
      unlocksTotal: 250,
      unlocksHardcore: 150,
    });

    render(<AchievementContributePanel />, {
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
    await userEvent.click(screen.getByRole('button', { name: /quick edit/i }));
    await userEvent.click(screen.getByRole('button', { name: /cancel editing/i }));

    // ASSERT
    expect(screen.getByRole('button', { name: /quick edit/i })).toBeVisible();
    expect(screen.queryByRole('button', { name: /cancel editing/i })).not.toBeInTheDocument();
  });
});
