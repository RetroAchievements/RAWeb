// eslint-disable-next-line no-restricted-imports -- fine in a test
import * as InertiajsReact from '@inertiajs/react';
import userEvent from '@testing-library/user-event';

import { createAuthenticatedUser } from '@/common/models';
import { usePersistedGameIdsCookie } from '@/features/games/hooks/usePersistedGameIdsCookie';
import {
  currentListViewAtom,
  currentPlayableListSortAtom,
  isLockedOnlyFilterEnabledAtom,
  isMissableOnlyFilterEnabledAtom,
} from '@/features/games/state/games.atoms';
import { render, screen } from '@/test';
import { createGame, createZiggyProps } from '@/test/factories';

import { GameAchievementSetToolbar } from '../GameAchievementSetToolbar';

vi.mock('@/features/games/hooks/usePersistedGameIdsCookie');

describe('Component: GameAchievementSetToolbar', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    // Mock router.reload to prevent actual HTTP requests in tests.
    vi.spyOn(InertiajsReact.router, 'reload').mockImplementation(vi.fn());
  });

  it('renders without crashing', () => {
    // ARRANGE
    const mockGame = createGame({ id: 123 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    const { container } = render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5}
        missableAchievementsCount={3}
        unlockedAchievementsCount={0}
      />,
      { pageProps: { backingGame: mockGame } },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are locked achievements and unlocked achievements, shows the locked only toggle', () => {
    // ARRANGE
    const mockGame = createGame({ id: 123 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5}
        missableAchievementsCount={0}
        unlockedAchievementsCount={1} // !!
      />,
      { pageProps: { backingGame: mockGame } },
    );

    // ASSERT
    expect(screen.getByText(/locked only/i)).toBeVisible();
  });

  it('given there are locked achievements and no unlocked achievements, shows the locked only toggle', () => {
    // ARRANGE
    const mockGame = createGame({ id: 123 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5}
        missableAchievementsCount={0}
        unlockedAchievementsCount={0} // !!
      />,
      { pageProps: { backingGame: mockGame } },
    );

    // ASSERT
    expect(screen.queryByText(/locked only/i)).not.toBeInTheDocument();
  });

  it('given there are no locked achievements, does not show the locked only toggle', () => {
    // ARRANGE
    const mockGame = createGame({ id: 123 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={0}
        missableAchievementsCount={3}
        unlockedAchievementsCount={0}
      />,
      { pageProps: { backingGame: mockGame } },
    );

    // ASSERT
    expect(screen.queryByText(/locked only/i)).not.toBeInTheDocument();
  });

  it('given there are missable achievements, shows the missable only toggle with count', () => {
    // ARRANGE
    const mockGame = createGame({ id: 123 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={0}
        missableAchievementsCount={7}
        unlockedAchievementsCount={0}
      />,
      { pageProps: { backingGame: mockGame } },
    );

    // ASSERT
    expect(screen.getByText(/missable only/i)).toBeVisible();
    expect(screen.getByText('7')).toBeVisible();
  });

  it('given there are no missable achievements, does not show the missable only toggle', () => {
    // ARRANGE
    const mockGame = createGame({ id: 123 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5}
        missableAchievementsCount={0}
        unlockedAchievementsCount={0}
      />,
      { pageProps: { backingGame: mockGame } },
    );

    // ASSERT
    expect(screen.queryByText(/missable only/i)).not.toBeInTheDocument();
  });

  it('given the user clicks the locked only toggle, updates the atom and cookie', async () => {
    // ARRANGE
    const mockGame = createGame({ id: 123 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5}
        missableAchievementsCount={3}
        unlockedAchievementsCount={1}
      />,
      {
        pageProps: { backingGame: mockGame },
        jotaiAtoms: [
          [isLockedOnlyFilterEnabledAtom, false],
          //
        ],
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /locked only/i }));

    // ASSERT
    expect(mockToggleGameId).toHaveBeenCalledWith(true);
  });

  it('given the locked only filter is already enabled and the user clicks it, disables it', async () => {
    // ARRANGE
    const mockGame = createGame({ id: 123 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5}
        missableAchievementsCount={3}
        unlockedAchievementsCount={1}
      />,
      {
        pageProps: { backingGame: mockGame },
        jotaiAtoms: [
          [isLockedOnlyFilterEnabledAtom, true],
          //
        ],
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /locked only/i }));

    // ASSERT
    expect(mockToggleGameId).toHaveBeenCalledWith(false);
  });

  it('given the user clicks the missable only toggle, updates the cookie', async () => {
    // ARRANGE
    const mockGame = createGame({ id: 123 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5}
        missableAchievementsCount={3}
        unlockedAchievementsCount={1}
      />,
      {
        pageProps: { backingGame: mockGame },
        jotaiAtoms: [
          [isMissableOnlyFilterEnabledAtom, false],
          //
        ],
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /missable only/i }));

    // ASSERT
    expect(mockToggleGameId).toHaveBeenCalledWith(true);
  });

  it('given the missable only filter is already enabled and the user clicks it, disables it', async () => {
    // ARRANGE
    const mockGame = createGame({ id: 123 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5}
        missableAchievementsCount={3}
        unlockedAchievementsCount={0}
      />,
      {
        pageProps: { backingGame: mockGame },
        jotaiAtoms: [
          [isMissableOnlyFilterEnabledAtom, true],
          //
        ],
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /missable only/i }));

    // ASSERT
    expect(mockToggleGameId).toHaveBeenCalledWith(false);
  });

  it('given both filters have counts, renders both toggle buttons', () => {
    // ARRANGE
    const mockGame = createGame({ id: 123 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={10}
        missableAchievementsCount={5}
        unlockedAchievementsCount={1}
      />,
      { pageProps: { backingGame: mockGame } },
    );

    // ASSERT
    expect(screen.getByText(/locked only/i)).toBeVisible();
    expect(screen.getByText(/missable only/i)).toBeVisible();
  });

  it('given there are leaderboards, shows the display mode toggle group', () => {
    // ARRANGE
    const mockGame = createGame({ id: 123 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5}
        missableAchievementsCount={3}
        unlockedAchievementsCount={1}
      />,
      {
        pageProps: {
          backingGame: mockGame,
          numLeaderboards: 10, // !!
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.getByRole('radio', { name: /achievements/i })).toBeVisible();
    expect(screen.getByRole('radio', { name: /leaderboards/i })).toBeVisible();
  });

  it('given there are no leaderboards, does not show the display mode toggle group', () => {
    // ARRANGE
    const mockGame = createGame({ id: 123 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5}
        missableAchievementsCount={3}
        unlockedAchievementsCount={1}
      />,
      {
        pageProps: {
          backingGame: mockGame,
          numLeaderboards: 0, // !!
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.queryByRole('radio', { name: /achievements/i })).not.toBeInTheDocument();
    expect(screen.queryByRole('radio', { name: /leaderboards/i })).not.toBeInTheDocument();
  });

  it('given the user clicks the leaderboards toggle button, switches to the leaderboards view', async () => {
    // ARRANGE
    const mockGame = createGame({ id: 123 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5}
        missableAchievementsCount={3}
        unlockedAchievementsCount={1}
      />,
      {
        pageProps: { backingGame: mockGame, numLeaderboards: 10, ziggy: createZiggyProps() },
        jotaiAtoms: [
          [currentListViewAtom, 'achievements'],
          //
        ],
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: /leaderboards/i }));

    // ASSERT
    expect(screen.getByRole('button', { name: /locked only/i })).toBeDisabled();
    expect(screen.getByRole('button', { name: /missable only/i })).toBeDisabled();
  });

  it('given the user clicks on the current toggle option, does not unset the value', async () => {
    // ARRANGE
    const mockGame = createGame({ id: 123 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5}
        missableAchievementsCount={3}
        unlockedAchievementsCount={1}
      />,
      {
        pageProps: { backingGame: mockGame, numLeaderboards: 10, ziggy: createZiggyProps() },
        jotaiAtoms: [
          [currentListViewAtom, 'achievements'],
          //
        ],
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: /achievements/i }));

    // ASSERT
    expect(screen.getByRole('radio', { name: /achievements/i })).toBeChecked();
  });

  it('given the current display mode is leaderboards and the user clicks the achievements toggle button, switches to the achievements view', async () => {
    // ARRANGE
    const mockGame = createGame({ id: 123 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5}
        missableAchievementsCount={3}
        unlockedAchievementsCount={1}
      />,
      {
        pageProps: {
          backingGame: mockGame,
          numLeaderboards: 10,
          ziggy: createZiggyProps(),
          defaultSort: 'normal',
        },
        jotaiAtoms: [
          [currentListViewAtom, 'leaderboards'],
          //
        ],
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('radio', { name: /achievements/i }));

    // ASSERT
    expect(screen.getByRole('button', { name: /locked only/i })).toBeEnabled();
    expect(screen.getByRole('button', { name: /missable only/i })).toBeEnabled();
    expect(screen.getByRole('button', { name: /unlocked first/i })).toBeEnabled();
  });

  it('given the current view is leaderboards but there are no leaderboards, automatically switches to achievements view', () => {
    // ARRANGE
    const mockGame = createGame({ id: 123 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5}
        missableAchievementsCount={3}
        unlockedAchievementsCount={1}
      />,
      {
        pageProps: { backingGame: mockGame, numLeaderboards: 0 }, // !! no leaderboards
        jotaiAtoms: [
          [currentListViewAtom, 'leaderboards'],
          //
        ],
      },
    );

    expect(screen.queryByRole('button', { name: /display mode/i })).not.toBeInTheDocument();
  });

  it('given the user changes the sort order, correctly calls the onChange handler', async () => {
    // ARRANGE
    const mockGame = createGame({ id: 123 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5}
        missableAchievementsCount={3}
        unlockedAchievementsCount={1}
      />,
      {
        pageProps: { backingGame: mockGame, numLeaderboards: 10, ziggy: createZiggyProps() },
        jotaiAtoms: [
          [currentPlayableListSortAtom, 'normal'],
          //
        ],
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /unlocked first/i }));
    await userEvent.click(screen.getByRole('menuitemcheckbox', { name: /won by \(most\)/i }));

    // ASSERT
    // ... the sort button should now show the new sort order ...
    expect(screen.getByRole('button', { name: /won by/i })).toBeVisible();
  });

  it('given the user has unlocked some (but not all) achievements, shows the Unlocked first sort option', async () => {
    // ARRANGE
    const mockGame = createGame({ id: 123, achievementsPublished: 5 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5} // !! some locked
        missableAchievementsCount={0}
        unlockedAchievementsCount={3} // !! some unlocked
      />,
      {
        pageProps: { backingGame: mockGame },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /display order/i }));

    // ASSERT
    expect(screen.getByRole('menuitemcheckbox', { name: 'Unlocked first' })).toBeInTheDocument();
  });

  it('given the user has unlocked no achievements, does not show the Unlocked first sort option', async () => {
    // ARRANGE
    const mockGame = createGame({ id: 123 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={10} // !! all locked
        missableAchievementsCount={0}
        unlockedAchievementsCount={0} // !! none unlocked
      />,
      {
        pageProps: { backingGame: mockGame },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /display order/i }));

    // ASSERT
    expect(
      screen.queryByRole('menuitemcheckbox', { name: 'Unlocked first' }),
    ).not.toBeInTheDocument();
  });

  it('given the user has unlocked all achievements, does not show the Unlocked first sort option', async () => {
    // ARRANGE
    const mockGame = createGame({ id: 123, achievementsPublished: 10 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={0} // !! none locked
        missableAchievementsCount={0}
        unlockedAchievementsCount={10} // !! all unlocked
      />,
      {
        pageProps: { backingGame: mockGame },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /display order/i }));

    // ASSERT
    expect(
      screen.queryByRole('menuitemcheckbox', { name: 'Unlocked first' }),
    ).not.toBeInTheDocument();
  });

  it('given the user changes the sort order to the non-default sort order, updates the URL with the sort parameter', async () => {
    // ARRANGE
    const mockGame = createGame({ id: 123, achievementsPublished: 10 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5}
        missableAchievementsCount={3}
        unlockedAchievementsCount={1}
      />,
      {
        pageProps: { backingGame: mockGame, defaultSort: 'displayOrder' },
        jotaiAtoms: [
          [currentPlayableListSortAtom, 'displayOrder'],
          //
        ],
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /display order/i }));
    await userEvent.click(screen.getByRole('menuitemcheckbox', { name: /points \(most\)/i }));

    // ASSERT
    expect(window.location.search).toContain('sort=points');
  });

  it('given the user changes the sort order to the default sort order, removes the sort parameter from the URL', async () => {
    // ARRANGE
    const mockGame = createGame({ id: 123, achievementsPublished: 10 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    // ... set an initial sort query param ...
    window.history.replaceState({}, '', '?sort=points');

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5}
        missableAchievementsCount={3}
        unlockedAchievementsCount={1}
      />,
      {
        pageProps: { backingGame: mockGame, defaultSort: 'displayOrder' },
        jotaiAtoms: [
          [currentPlayableListSortAtom, 'points'],
          //
        ],
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /points/i }));
    await userEvent.click(screen.getByRole('menuitemcheckbox', { name: 'Display order (first)' }));

    // ASSERT
    expect(window.location.search).not.toContain('sort');
  });

  it('given the user is not logged in, does not show auth required sorting options', async () => {
    // ARRANGE
    const mockGame = createGame({ id: 123, achievementsPublished: 10 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5}
        missableAchievementsCount={3}
        unlockedAchievementsCount={1}
      />,
      {
        pageProps: {
          backingGame: mockGame,
          numLeaderboards: 10,
          ziggy: createZiggyProps(),
          defaultSort: 'normal',
        },
        jotaiAtoms: [
          [currentListViewAtom, 'leaderboards'],
          //
        ],
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /display order/i }));

    // ASSERT
    expect(
      screen.queryByRole('menuitemcheckbox', { name: 'My Rank (best)' }),
    ).not.toBeInTheDocument();
    expect(
      screen.queryByRole('menuitemcheckbox', { name: 'My Rank (worst)' }),
    ).not.toBeInTheDocument();
  });

  it('given the user is logged in, does show auth required sorting options', async () => {
    // ARRANGE
    const mockGame = createGame({ id: 123, achievementsPublished: 10 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5}
        missableAchievementsCount={3}
        unlockedAchievementsCount={1}
      />,
      {
        pageProps: {
          backingGame: mockGame,
          numLeaderboards: 10,
          ziggy: createZiggyProps(),
          defaultSort: 'normal',
          auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) },
        },
        jotaiAtoms: [
          [currentListViewAtom, 'leaderboards'],
          //
        ],
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /display order/i }));

    // ASSERT
    expect(screen.getByRole('menuitemcheckbox', { name: 'My Rank (best)' })).toBeInTheDocument();
    expect(screen.getByRole('menuitemcheckbox', { name: 'My Rank (worst)' })).toBeInTheDocument();
  });
});
