import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import { createGame, createGameAchievementSet, createSystem } from '@/test/factories';

import { SubsetConfigurationButton } from './SubsetConfigurationButton';

const mockReload = vi.fn();
Object.defineProperty(window, 'location', {
  value: { ...window.location, reload: mockReload },
  writable: true,
});

describe('Component: SubsetConfigurationButton', () => {
  beforeEach(() => {
    mockReload.mockClear();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const game = createGame();
    const selectableGameAchievementSets = [
      createGameAchievementSet({ type: 'core' }),
      createGameAchievementSet({ type: 'bonus' }),
    ];

    const { container } = render(<SubsetConfigurationButton />, {
      pageProps: {
        game,
        auth: { user: createAuthenticatedUser() },
        selectableGameAchievementSets,
        userGameAchievementSetPreferences: {},
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user is not authenticated, does not render the button', () => {
    // ARRANGE
    const game = createGame();
    const selectableGameAchievementSets = [
      createGameAchievementSet({ type: 'core' }),
      createGameAchievementSet({ type: 'bonus' }),
    ];

    render(<SubsetConfigurationButton />, {
      pageProps: {
        game,
        auth: null, // !!
        selectableGameAchievementSets,
        userGameAchievementSetPreferences: {},
      },
    });

    // ASSERT
    expect(screen.queryByRole('button', { name: /subset configuration/i })).not.toBeInTheDocument();
  });

  it('given the game is a standalone, does not render the button', () => {
    // ARRANGE
    const standaloneSystem = createSystem({
      id: 102, // !!
    });
    const game = createGame({ system: standaloneSystem });
    const selectableGameAchievementSets = [
      createGameAchievementSet({ type: 'core' }),
      createGameAchievementSet({ type: 'bonus' }),
    ];

    render(<SubsetConfigurationButton />, {
      pageProps: {
        game,
        auth: { user: createAuthenticatedUser() },
        selectableGameAchievementSets,
        userGameAchievementSetPreferences: {},
      },
    });

    // ASSERT
    expect(screen.queryByRole('button', { name: /subset configuration/i })).not.toBeInTheDocument();
  });

  it('given there is only a core set, does not render the button', () => {
    // ARRANGE
    const game = createGame();
    const selectableGameAchievementSets = [
      createGameAchievementSet({
        type: 'core', // !!
      }),
    ];

    render(<SubsetConfigurationButton />, {
      pageProps: {
        game,
        auth: { user: createAuthenticatedUser() },
        selectableGameAchievementSets,
        userGameAchievementSetPreferences: {},
      },
    });

    // ASSERT
    expect(screen.queryByRole('button', { name: /subset configuration/i })).not.toBeInTheDocument();
  });

  it('given there are configurable sets, renders the button', () => {
    // ARRANGE
    const game = createGame();
    const selectableGameAchievementSets = [
      createGameAchievementSet({ type: 'core' }),
      createGameAchievementSet({ type: 'bonus' }),
    ];

    render(<SubsetConfigurationButton />, {
      pageProps: {
        game,
        auth: { user: createAuthenticatedUser() },
        selectableGameAchievementSets,
        userGameAchievementSetPreferences: {},
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /subset configuration/i })).toBeVisible();
  });

  it('given the button is clicked, opens the dialog', async () => {
    // ARRANGE
    const game = createGame();
    const selectableGameAchievementSets = [
      createGameAchievementSet({ type: 'core' }),
      createGameAchievementSet({ type: 'bonus' }),
    ];

    render(<SubsetConfigurationButton />, {
      pageProps: {
        game,
        auth: { user: createAuthenticatedUser() },
        selectableGameAchievementSets,
        userGameAchievementSetPreferences: {},
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /subset configuration/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByRole('dialog')).toBeVisible();
    });
  });

  it('given the dialog is open and the form is submitted successfully, closes the dialog', async () => {
    // ARRANGE
    vi.spyOn(axios, 'put').mockResolvedValueOnce({ data: { success: true } });

    const game = createGame();
    const selectableGameAchievementSets = [
      createGameAchievementSet({ type: 'core' }),
      createGameAchievementSet({ type: 'bonus' }),
    ];

    render(<SubsetConfigurationButton />, {
      pageProps: {
        game,
        auth: { user: createAuthenticatedUser() },
        selectableGameAchievementSets,
        userGameAchievementSetPreferences: {},
      },
    });

    await userEvent.click(screen.getByRole('button', { name: /subset configuration/i }));

    // ... wait for dialog to open ...
    await waitFor(() => {
      expect(screen.getByRole('dialog')).toBeVisible();
    });

    // ACT
    const switches = screen.getAllByRole('switch');
    await userEvent.click(switches[0]);
    await userEvent.click(screen.getByRole('button', { name: /save/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });
    expect(mockReload).toHaveBeenCalled();
  });

  it('filters out will_be_* type sets from configurable sets', async () => {
    // ARRANGE
    const game = createGame();
    const selectableGameAchievementSets = [
      createGameAchievementSet({ id: 1, type: 'core', title: 'Core Set' }),
      createGameAchievementSet({ id: 2, type: 'bonus', title: 'Bonus Set' }),
      createGameAchievementSet({ id: 3, type: 'will_be_bonus', title: 'Future Bonus' }),
    ];

    render(<SubsetConfigurationButton />, {
      pageProps: {
        game,
        auth: { user: createAuthenticatedUser() },
        selectableGameAchievementSets,
        userGameAchievementSetPreferences: {},
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /subset configuration/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByRole('dialog')).toBeVisible();
    });

    // ... the dialog content should only show the bonus set, not the will_be_bonus set ...
    await waitFor(() => {
      expect(screen.getByText('Bonus Set')).toBeVisible();
    });
    expect(screen.queryByText('Future Bonus')).not.toBeInTheDocument();
  });

  it('given all non-core sets are will_be_* types, does not render the button', () => {
    // ARRANGE
    const game = createGame();
    const selectableGameAchievementSets = [
      createGameAchievementSet({ type: 'core' }),
      createGameAchievementSet({ type: 'will_be_bonus' }),
      createGameAchievementSet({ type: 'will_be_specialty' }),
    ];

    render(<SubsetConfigurationButton />, {
      pageProps: {
        game,
        auth: { user: createAuthenticatedUser() },
        selectableGameAchievementSets,
        userGameAchievementSetPreferences: {},
      },
    });

    // ASSERT
    expect(screen.queryByRole('button', { name: /subset configuration/i })).not.toBeInTheDocument();
  });
});
