import userEvent from '@testing-library/user-event';

import { usePersistedGameIdsCookie } from '@/features/games/hooks/usePersistedGameIdsCookie';
import {
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
      <GameAchievementSetToolbar lockedAchievementsCount={5} missableAchievementsCount={3} />,
      { pageProps: { backingGame: mockGame } },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are locked achievements, shows the locked only toggle', () => {
    // ARRANGE
    const mockGame = createGame({ id: 123 });
    const mockToggleGameId = vi.fn();

    vi.mocked(usePersistedGameIdsCookie).mockReturnValue({
      isGameIdInCookie: vi.fn().mockReturnValue(false),
      toggleGameId: mockToggleGameId,
    });

    render(
      <GameAchievementSetToolbar lockedAchievementsCount={5} missableAchievementsCount={0} />,
      { pageProps: { backingGame: mockGame } },
    );

    // ASSERT
    expect(screen.getByText(/locked only/i)).toBeVisible();
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
      <GameAchievementSetToolbar lockedAchievementsCount={0} missableAchievementsCount={3} />,
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
      <GameAchievementSetToolbar lockedAchievementsCount={0} missableAchievementsCount={7} />,
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
      <GameAchievementSetToolbar lockedAchievementsCount={5} missableAchievementsCount={0} />,
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
      <GameAchievementSetToolbar lockedAchievementsCount={5} missableAchievementsCount={3} />,
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
      <GameAchievementSetToolbar lockedAchievementsCount={5} missableAchievementsCount={3} />,
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
      <GameAchievementSetToolbar lockedAchievementsCount={5} missableAchievementsCount={3} />,
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
      <GameAchievementSetToolbar lockedAchievementsCount={5} missableAchievementsCount={3} />,
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
      <GameAchievementSetToolbar lockedAchievementsCount={10} missableAchievementsCount={5} />,
      { pageProps: { backingGame: mockGame } },
    );

    // ASSERT
    expect(screen.getByText(/locked only/i)).toBeVisible();
    expect(screen.getByText(/missable only/i)).toBeVisible();
  });
});
