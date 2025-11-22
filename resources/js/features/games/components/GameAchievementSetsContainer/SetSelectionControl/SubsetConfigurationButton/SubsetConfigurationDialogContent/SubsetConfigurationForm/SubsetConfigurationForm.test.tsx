import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { createAuthenticatedUser, createAuthenticatedUserPreferences } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import { createGameAchievementSet, createUserGameAchievementSetPreference } from '@/test/factories';

import { SubsetConfigurationForm } from './SubsetConfigurationForm';

describe('Component: SubsetConfigurationForm', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <SubsetConfigurationForm configurableSets={[]} onSubmitSuccess={vi.fn()} />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          userGameAchievementSetPreferences: {},
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given configurable sets are provided, renders a switch for each set', () => {
    // ARRANGE
    const sets = [
      createGameAchievementSet({ id: 1, title: 'Bonus Set' }),
      createGameAchievementSet({ id: 2, title: 'Challenge Set' }),
    ];

    render(<SubsetConfigurationForm configurableSets={sets} onSubmitSuccess={vi.fn()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        userGameAchievementSetPreferences: {},
      },
    });

    // ASSERT
    expect(screen.getByText('Bonus Set')).toBeVisible();
    expect(screen.getByText('Challenge Set')).toBeVisible();
  });

  it('given a set has no title, displays Base Set as the label', () => {
    // ARRANGE
    const sets = [
      createGameAchievementSet({
        id: 1,
        title: null, // !!
      }),
    ];

    render(<SubsetConfigurationForm configurableSets={sets} onSubmitSuccess={vi.fn()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        userGameAchievementSetPreferences: {},
      },
    });

    // ASSERT
    expect(screen.getByText(/base set/i)).toBeVisible();
  });

  it('given a user clicks a switch, toggles the value and enables the save button', async () => {
    // ARRANGE
    const sets = [createGameAchievementSet({ id: 1, title: 'Bonus Set' })];

    render(<SubsetConfigurationForm configurableSets={sets} onSubmitSuccess={vi.fn()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        userGameAchievementSetPreferences: {},
      },
    });

    const saveButton = screen.getByRole('button', { name: /save/i });

    // ... initially, the form isn't dirty, so save should be disabled ...
    expect(saveButton).toBeDisabled();

    // ACT
    const switches = screen.getAllByRole('switch');
    await userEvent.click(switches[0]);

    // ASSERT
    await waitFor(() => {
      expect(saveButton).toBeEnabled();
    });
  });

  it('given the form is submitted successfully, calls onSubmitSuccess and shows a success toast', async () => {
    // ARRANGE
    vi.spyOn(axios, 'put').mockResolvedValueOnce({ data: { success: true } });
    const onSubmitSuccess = vi.fn();

    const sets = [createGameAchievementSet({ id: 1, title: 'Bonus Set' })];

    render(<SubsetConfigurationForm configurableSets={sets} onSubmitSuccess={onSubmitSuccess} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        userGameAchievementSetPreferences: {},
      },
    });

    // ACT
    const switches = screen.getAllByRole('switch');
    await userEvent.click(switches[0]);
    await userEvent.click(screen.getByRole('button', { name: /save/i }));

    // ASSERT
    await waitFor(() => {
      expect(onSubmitSuccess).toHaveBeenCalled();
    });
    expect(screen.getByText(/saved!/i)).toBeVisible();
  });

  it('given the form submission fails, shows an error toast', async () => {
    // ARRANGE
    vi.spyOn(axios, 'put').mockRejectedValueOnce(new Error('Network error'));

    const sets = [createGameAchievementSet({ id: 1, title: 'Bonus Set' })];

    render(<SubsetConfigurationForm configurableSets={sets} onSubmitSuccess={vi.fn()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        userGameAchievementSetPreferences: {},
      },
    });

    // ACT
    const switches = screen.getAllByRole('switch');
    await userEvent.click(switches[0]);
    await userEvent.click(screen.getByRole('button', { name: /save/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/something went wrong/i)).toBeVisible();
    });
  });

  it('given the user has existing preferences, reflects them in the switches', () => {
    // ARRANGE
    const sets = [
      createGameAchievementSet({ id: 1, title: 'Bonus Set' }),
      createGameAchievementSet({ id: 2, title: 'Challenge Set' }),
    ];

    const preferences = {
      1: createUserGameAchievementSetPreference({ gameAchievementSetId: 1, optedIn: false }),
      2: createUserGameAchievementSetPreference({ gameAchievementSetId: 2, optedIn: true }),
    };

    render(<SubsetConfigurationForm configurableSets={sets} onSubmitSuccess={vi.fn()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        userGameAchievementSetPreferences: preferences,
      },
    });

    // ASSERT
    const switches = screen.getAllByRole('switch');
    expect(switches[0]).not.toBeChecked();
    expect(switches[1]).toBeChecked();
  });

  it('given the user is globally opted out, unconfigured sets default to opted out', () => {
    // ARRANGE
    const sets = [createGameAchievementSet({ id: 1, title: 'Bonus Set' })];

    render(<SubsetConfigurationForm configurableSets={sets} onSubmitSuccess={vi.fn()} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            preferences: createAuthenticatedUserPreferences({
              isGloballyOptedOutOfSubsets: true, // !!
              prefersAbsoluteDates: false,
              shouldAlwaysBypassContentWarnings: false,
            }),
          }),
        },
        userGameAchievementSetPreferences: {},
      },
    });

    // ASSERT
    const switches = screen.getAllByRole('switch');
    expect(switches[0]).not.toBeChecked();
  });

  it('given the user is globally opted in, unconfigured sets default to opted in', () => {
    // ARRANGE
    const sets = [createGameAchievementSet({ id: 1, title: 'Bonus Set' })];

    render(<SubsetConfigurationForm configurableSets={sets} onSubmitSuccess={vi.fn()} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            preferences: createAuthenticatedUserPreferences({
              isGloballyOptedOutOfSubsets: false, // !!
              prefersAbsoluteDates: false,
              shouldAlwaysBypassContentWarnings: false,
            }),
          }),
        },
        userGameAchievementSetPreferences: {},
      },
    });

    // ASSERT
    const switches = screen.getAllByRole('switch');
    expect(switches[0]).toBeChecked();
  });

  it('given multiple sets have changes, sends all changed preferences to the API', async () => {
    // ARRANGE
    const putSpy = vi.spyOn(axios, 'put').mockResolvedValueOnce({ data: { success: true } });

    const sets = [
      createGameAchievementSet({ id: 1, title: 'Bonus Set' }),
      createGameAchievementSet({ id: 2, title: 'Challenge Set' }),
      createGameAchievementSet({ id: 3, title: 'Challenge Set 2' }),
    ];

    render(<SubsetConfigurationForm configurableSets={sets} onSubmitSuccess={vi.fn()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        userGameAchievementSetPreferences: {
          1: createUserGameAchievementSetPreference({ gameAchievementSetId: 1, optedIn: true }),
        },
      },
    });

    // ACT
    const switches = screen.getAllByRole('switch');
    await userEvent.click(switches[0]);
    await userEvent.click(switches[1]);
    await userEvent.click(screen.getByRole('button', { name: /save/i }));

    // ASSERT
    await waitFor(() => {
      expect(putSpy).toHaveBeenCalledWith(
        expect.anything(),
        expect.objectContaining({
          preferences: expect.arrayContaining([
            { gameAchievementSetId: 1, optedIn: false },
            { gameAchievementSetId: 2, optedIn: false },
          ]),
        }),
      );
    });
  });

  it('given the mutation is pending, disables the save button', async () => {
    // ARRANGE
    vi.spyOn(axios, 'put').mockImplementation(
      () => new Promise((resolve) => setTimeout(resolve, 1000)),
    );

    const sets = [createGameAchievementSet({ id: 1, title: 'Bonus Set' })];

    render(<SubsetConfigurationForm configurableSets={sets} onSubmitSuccess={vi.fn()} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        userGameAchievementSetPreferences: {},
      },
    });

    // ACT
    const switches = screen.getAllByRole('switch');
    await userEvent.click(switches[0]);
    const saveButton = screen.getByRole('button', { name: /save/i });
    await userEvent.click(saveButton);

    // ASSERT
    expect(saveButton).toBeDisabled();
  });
});
