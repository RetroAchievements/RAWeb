import userEvent from '@testing-library/user-event';

import { usePersistedGameIdsCookie } from '@/features/games/hooks/usePersistedGameIdsCookie';
import {
  currentListViewAtom,
  currentPlayableListSortAtom,
  isLockedOnlyFilterEnabledAtom,
  isMissableOnlyFilterEnabledAtom,
} from '@/features/games/state/games.atoms';
import { render, screen } from '@/test';
import { createGame } from '@/test/factories';

import { GameAchievementSetToolbar } from '../GameAchievementSetToolbar';

vi.mock('@/features/games/hooks/usePersistedGameIdsCookie');

describe('Component: GameAchievementSetToolbar', () => {
  beforeEach(() => {
    vi.clearAllMocks();
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

    const initialAtomValues: [any, any][] = [[isLockedOnlyFilterEnabledAtom, false]];

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5}
        missableAchievementsCount={3}
        unlockedAchievementsCount={1}
      />,
      { pageProps: { backingGame: mockGame }, jotaiAtoms: initialAtomValues },
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

    const initialAtomValues: [any, any][] = [[isLockedOnlyFilterEnabledAtom, true]];

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5}
        missableAchievementsCount={3}
        unlockedAchievementsCount={1}
      />,
      { pageProps: { backingGame: mockGame }, jotaiAtoms: initialAtomValues },
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

    const initialAtomValues: [any, any][] = [[isMissableOnlyFilterEnabledAtom, false]];

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5}
        missableAchievementsCount={3}
        unlockedAchievementsCount={1}
      />,
      { pageProps: { backingGame: mockGame }, jotaiAtoms: initialAtomValues },
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

    const initialAtomValues: [any, any][] = [[isMissableOnlyFilterEnabledAtom, true]];

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5}
        missableAchievementsCount={3}
        unlockedAchievementsCount={0}
      />,
      { pageProps: { backingGame: mockGame }, jotaiAtoms: initialAtomValues },
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

  it('given there are leaderboards, shows the display mode button', () => {
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
      { pageProps: { backingGame: mockGame, numLeaderboards: 10 } }, // !!
    );

    // ASSERT
    expect(screen.getByRole('button', { name: /display mode/i })).toBeVisible();
  });

  it('given there are no leaderboards, does not show the display mode button', () => {
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
      { pageProps: { backingGame: mockGame, numLeaderboards: 0 } }, // !!
    );

    // ASSERT
    expect(screen.queryByRole('button', { name: /display mode/i })).not.toBeInTheDocument();
  });

  it('given the user clicks the display mode button and selects Leaderboards, switches to the leaderboards view', async () => {
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
        pageProps: { backingGame: mockGame, numLeaderboards: 10 },
        jotaiAtoms: [
          [currentListViewAtom, 'achievements'],
          //
        ],
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /display mode/i }));
    await userEvent.click(screen.getByText(/leaderboards/i));

    // ASSERT
    expect(screen.getByRole('button', { name: /locked only/i })).toBeDisabled();
    expect(screen.getByRole('button', { name: /missable only/i })).toBeDisabled();
  });

  it('given the current display mode is leaderboards and the user selects achievements, switches to the achievements view', async () => {
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
        pageProps: { backingGame: mockGame, numLeaderboards: 10 },
        jotaiAtoms: [
          [currentListViewAtom, 'leaderboards'],
          //
        ],
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /display mode/i }));
    await userEvent.click(screen.getByText(/achievements/i));

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

    const initialAtomValues: [any, any][] = [
      [currentListViewAtom, 'leaderboards'], // !! viewing leaderboards
      //
    ];

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5}
        missableAchievementsCount={3}
        unlockedAchievementsCount={1}
      />,
      {
        pageProps: { backingGame: mockGame, numLeaderboards: 0 }, // !! no leaderboards
        jotaiAtoms: initialAtomValues,
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

    const initialAtomValues: [any, any][] = [
      [currentPlayableListSortAtom, 'normal'], // !! starting sort
      //
    ];

    render(
      <GameAchievementSetToolbar
        lockedAchievementsCount={5}
        missableAchievementsCount={3}
        unlockedAchievementsCount={1}
      />,
      {
        pageProps: { backingGame: mockGame, numLeaderboards: 10 },
        jotaiAtoms: initialAtomValues,
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
});
