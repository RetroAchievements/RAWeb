import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import { act } from 'react';
import { useMedia } from 'react-use';
import { route } from 'ziggy-js';

import { fireEvent, render, screen } from '@/test';
import {
  createAchievement,
  createAchievementSet,
  createGame,
  createGameAchievementSet,
} from '@/test/factories';

import { ProximityAchievements } from './ProximityAchievements';

vi.mock('react-use', async (importOriginal) => ({
  ...(await importOriginal()),
  useMedia: vi.fn().mockReturnValue(false),
}));

describe('Component: ProximityAchievements', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const achievement = createAchievement({ game: createGame() });
    const proximityAchievements = [
      createAchievement({ id: 1, title: 'First' }),
      createAchievement({ id: 2, title: 'Second' }),
    ];

    const { container } = render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 10,
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given no proximity achievements, renders nothing', () => {
    // ARRANGE
    const achievement = createAchievement({ game: createGame() });

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements: null,
        promotedAchievementCount: 0,
      },
    });

    // ASSERT
    expect(screen.queryByRole('heading', { name: /more from this set/i })).not.toBeInTheDocument();
  });

  it('given only the current achievement in the list, renders nothing', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 55, game: createGame() });

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements: [createAchievement({ id: 55 })],
        promotedAchievementCount: 1,
      },
    });

    // ASSERT
    expect(screen.queryByRole('heading', { name: /more from this set/i })).not.toBeInTheDocument();
  });

  it('shows the heading label', () => {
    // ARRANGE
    const achievement = createAchievement({ game: createGame() });
    const proximityAchievements = [createAchievement({ id: 1 }), createAchievement({ id: 2 })];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 10,
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /more from this set/i })).toBeVisible();
  });

  it('renders all proximity achievement titles', () => {
    // ARRANGE
    const achievement = createAchievement({ game: createGame() });
    const proximityAchievements = [
      createAchievement({ id: 1, title: 'Alpha Achievement' }),
      createAchievement({ id: 2, title: 'Beta Achievement' }),
      createAchievement({ id: 3, title: 'Gamma Achievement' }),
    ];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 20,
      },
    });

    // ASSERT
    expect(screen.getByText('Alpha Achievement')).toBeVisible();
    expect(screen.getByText('Beta Achievement')).toBeVisible();
    expect(screen.getByText('Gamma Achievement')).toBeVisible();
  });

  it('shows a gray checkmark for softcore-unlocked achievements', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 1, game: createGame() });
    const proximityAchievements = [
      createAchievement({ id: 1, title: 'Locked One' }),
      createAchievement({
        id: 2,
        title: 'Softcore One',
        unlockedAt: '2024-01-15T00:00:00Z',
        unlockedHardcoreAt: undefined,
      }),
    ];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 10,
      },
    });

    // ASSERT
    const checkIcon = screen.getByTestId('unlock-check-2');
    expect(checkIcon).toBeVisible();
    expect(checkIcon.className).toContain('text-neutral-400');
    expect(checkIcon).toHaveAttribute('aria-label', 'Unlocked');
  });

  it('shows a gold checkmark for hardcore-unlocked achievements', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 1, game: createGame() });
    const proximityAchievements = [
      createAchievement({ id: 1, title: 'Locked One' }),
      createAchievement({
        id: 2,
        title: 'Hardcore One',
        unlockedAt: '2024-01-15T00:00:00Z',
        unlockedHardcoreAt: '2024-01-15T00:00:00Z',
      }),
    ];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 10,
      },
    });

    // ASSERT
    const checkIcon = screen.getByTestId('unlock-check-2');
    expect(checkIcon).toBeVisible();
    expect(checkIcon.className).toContain('text-[gold]');
    expect(checkIcon).toHaveAttribute('aria-label', 'Unlocked in hardcore');
  });

  it('does not show a checkmark for locked achievements', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 1, game: createGame() });
    const proximityAchievements = [
      createAchievement({
        id: 2,
        title: 'Locked One',
        unlockedAt: undefined,
        unlockedHardcoreAt: undefined,
      }),
      createAchievement({ id: 3, title: 'Another Locked' }),
    ];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 10,
      },
    });

    // ASSERT
    expect(screen.queryByTestId('unlock-check-2')).not.toBeInTheDocument();
  });

  it('styles the current achievement title differently from other titles', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 77, game: createGame() });
    const proximityAchievements = [
      createAchievement({ id: 76, title: 'Before' }),
      createAchievement({ id: 77, title: 'Current' }),
      createAchievement({ id: 78, title: 'After' }),
    ];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 10,
      },
    });

    // ASSERT
    const currentTitle = screen.getByText('Current');
    expect(currentTitle.className).not.toContain('text-link');

    const otherTitle = screen.getByText('Before');
    expect(otherTitle.className).toContain('text-link');
  });

  it('shows "View all N achievements" with the correct count', () => {
    // ARRANGE
    const achievement = createAchievement({ game: createGame({ id: 100 }) });
    const proximityAchievements = [createAchievement({ id: 1 }), createAchievement({ id: 2 })];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 35,
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /view all 35 achievements/i })).toBeVisible();
  });

  it('given a subset backing game, links "View all" to the backing game with the set param', () => {
    // ARRANGE
    const backingGame = createGame({ id: 200 });
    const gameAchievementSet = createGameAchievementSet({
      achievementSet: createAchievementSet({ id: 300 }),
    });
    const achievement = createAchievement({ game: createGame({ id: 999 }) });
    const proximityAchievements = [createAchievement({ id: 1 }), createAchievement({ id: 2 })];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 15,
        backingGame,
        gameAchievementSet,
      },
    });

    // ASSERT
    expect(vi.mocked(route)).toHaveBeenCalledWith('game.show', {
      game: 200,
      _query: { set: 300 },
    });
  });

  it('given promotedAchievementCount is zero, does not show the "View all" link', () => {
    // ARRANGE
    const achievement = createAchievement({ game: createGame() });
    const proximityAchievements = [createAchievement({ id: 1 }), createAchievement({ id: 2 })];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 0,
      },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /view all/i })).not.toBeInTheDocument();
  });

  it('given no backing game, links "View all" to the right game page', () => {
    // ARRANGE
    const achievement = createAchievement({ game: createGame({ id: 500 }) });
    const proximityAchievements = [createAchievement({ id: 1 }), createAchievement({ id: 2 })];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 20,
      },
    });

    // ASSERT
    expect(vi.mocked(route)).toHaveBeenCalledWith('game.show', { game: 500 });
  });

  it('gives non-current items a button role and tabIndex for keyboard access', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 10, game: createGame() });
    const proximityAchievements = [
      createAchievement({ id: 10, title: 'Current' }),
      createAchievement({ id: 11, title: 'Other' }),
    ];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 5,
      },
    });

    // ASSERT
    // ... non-current items get role="button" so they're keyboard accessible ...
    const otherButton = screen.getByRole('button', { name: /other/i });
    expect(otherButton).toHaveAttribute('tabindex', '0');

    // ... the current item should not be interactive ...
    expect(screen.queryByRole('button', { name: /current/i })).not.toBeInTheDocument();
  });

  it('activates a non-current item when the user presses Enter', async () => {
    // ARRANGE
    const achievement = createAchievement({ id: 10, game: createGame() });
    const proximityAchievements = [
      createAchievement({ id: 10, title: 'Current' }),
      createAchievement({ id: 11, title: 'Other' }),
    ];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 5,
      },
    });

    // ACT
    const otherButton = screen.getByRole('button', { name: /other/i });
    otherButton.focus();
    await userEvent.keyboard('{Enter}');

    // ASSERT
    expect(vi.mocked(route)).toHaveBeenCalledWith('achievement2.show', { achievement: 11 });
  });

  it('given the current item is clicked, does not trigger navigation', async () => {
    // ARRANGE
    const achievement = createAchievement({ id: 10, game: createGame() });
    const proximityAchievements = [
      createAchievement({ id: 10, title: 'Current' }),
      createAchievement({ id: 11, title: 'Other' }),
    ];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 5,
      },
    });

    // ACT
    await userEvent.click(screen.getByText('Current'));

    // ASSERT
    expect(router.visit).not.toHaveBeenCalled();
  });

  it('given the current item is hovered, does not trigger prefetch', async () => {
    // ARRANGE
    const achievement = createAchievement({ id: 10, game: createGame() });
    const proximityAchievements = [
      createAchievement({ id: 10, title: 'Current' }),
      createAchievement({ id: 11, title: 'Other' }),
    ];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 5,
      },
    });

    // ACT
    await userEvent.hover(screen.getByText('Current'));

    // ASSERT
    expect(router.prefetch).not.toHaveBeenCalled();
  });

  it('given a non-current item is clicked, triggers navigation after the animation', () => {
    // ARRANGE
    vi.useFakeTimers();

    const achievement = createAchievement({ id: 10, game: createGame() });
    const proximityAchievements = [
      createAchievement({ id: 10, title: 'Current' }),
      createAchievement({ id: 11, title: 'Other' }),
    ];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 5,
      },
    });

    // ACT
    fireEvent.click(screen.getByRole('button', { name: /other/i }));
    const list = screen.getByRole('list');
    act(() => {
      vi.advanceTimersByTime(300);
    });

    // ASSERT
    expect(list).toHaveStyle('pointer-events: none');
    expect(router.visit).toHaveBeenCalled();

    vi.useRealTimers();
  });

  it('given a non-current item is hovered, triggers prefetch after a delay', () => {
    // ARRANGE
    vi.useFakeTimers();

    const achievement = createAchievement({ id: 10, game: createGame() });
    const proximityAchievements = [
      createAchievement({ id: 10, title: 'Current' }),
      createAchievement({ id: 11, title: 'Other' }),
    ];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 5,
      },
    });

    // ACT
    fireEvent.mouseEnter(screen.getByRole('button', { name: /other/i }));
    act(() => {
      vi.advanceTimersByTime(500);
    });

    // ASSERT
    expect(router.prefetch).toHaveBeenCalled();

    vi.useRealTimers();
  });

  it('given the viewport is below the lg breakpoint, navigates immediately on click without animation delay', () => {
    // ARRANGE
    vi.mocked(useMedia).mockReturnValue(true);

    const achievement = createAchievement({ id: 10, game: createGame() });
    const proximityAchievements = [
      createAchievement({ id: 10, title: 'Current' }),
      createAchievement({ id: 11, title: 'Other' }),
    ];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 5,
      },
    });

    // ACT
    fireEvent.click(screen.getByRole('button', { name: /other/i }));

    // ASSERT
    // ... navigation should fire synchronously, no timer advancement needed ...
    expect(router.visit).toHaveBeenCalled();
  });

  it('shows points and unlock percentage per item', () => {
    // ARRANGE
    const achievement = createAchievement({ game: createGame() });
    const proximityAchievements = [
      createAchievement({ id: 1, title: 'Test Ach', points: 25, unlockPercentage: '0.852' }),
      createAchievement({ id: 2, points: 10 }),
    ];

    render(<ProximityAchievements />, {
      pageProps: {
        achievement,
        proximityAchievements,
        promotedAchievementCount: 10,
      },
    });

    // ASSERT
    expect(screen.getByText(/25 points/)).toBeVisible();
    expect(screen.getByText(/85\.2%/)).toBeVisible();
  });
});
