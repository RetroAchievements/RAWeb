import { BaseDialog } from '@/common/components/+vendor/BaseDialog';
import { createAuthenticatedUser, createAuthenticatedUserPreferences } from '@/common/models';
import { render, screen } from '@/test';
import { createGameAchievementSet } from '@/test/factories';

import { SubsetConfigurationDialogContent } from './SubsetConfigurationDialogContent';

describe('Component: SubsetConfigurationDialogContent', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <BaseDialog open={true}>
        <SubsetConfigurationDialogContent configurableSets={[]} onSubmitSuccess={vi.fn()} />
      </BaseDialog>,
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

  it('displays the dialog title', () => {
    // ARRANGE
    render(
      <BaseDialog open={true}>
        <SubsetConfigurationDialogContent configurableSets={[]} onSubmitSuccess={vi.fn()} />
      </BaseDialog>,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          userGameAchievementSetPreferences: {},
        },
      },
    );

    // ASSERT
    expect(screen.getByText(/subset configuration/i)).toBeVisible();
  });

  it('displays the emulator version alert', () => {
    // ARRANGE
    render(
      <BaseDialog open={true}>
        <SubsetConfigurationDialogContent configurableSets={[]} onSubmitSuccess={vi.fn()} />
      </BaseDialog>,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          userGameAchievementSetPreferences: {},
        },
      },
    );

    // ASSERT
    expect(
      screen.getByText(/if subsets aren't working or if every subset still requires a patch/i),
    ).toBeVisible();
  });

  it('given the user is globally opted out, shows the opted out message', () => {
    // ARRANGE
    render(
      <BaseDialog open={true}>
        <SubsetConfigurationDialogContent configurableSets={[]} onSubmitSuccess={vi.fn()} />
      </BaseDialog>,
      {
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
      },
    );

    // ASSERT
    expect(screen.getByText(/globally opted out of all subsets/i)).toBeVisible();
    expect(
      screen.getByText(/use the toggles below to opt in to specific sets for this game/i),
    ).toBeVisible();
  });

  it('given the user is globally opted in, shows the opted in message', () => {
    // ARRANGE
    render(
      <BaseDialog open={true}>
        <SubsetConfigurationDialogContent configurableSets={[]} onSubmitSuccess={vi.fn()} />
      </BaseDialog>,
      {
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
      },
    );

    // ASSERT
    expect(screen.getByText(/globally opted in to all subsets/i)).toBeVisible();
    expect(
      screen.getByText(/use the toggles below to opt out of specific sets for this game/i),
    ).toBeVisible();
  });

  it('renders the subset configuration form with correct fields', () => {
    // ARRANGE
    const configurableSets = [
      createGameAchievementSet({ id: 1, title: 'Bonus Set' }),
      createGameAchievementSet({ id: 2, title: 'Challenge Set' }),
    ];
    const onSubmitSuccess = vi.fn();

    render(
      <BaseDialog open={true}>
        <SubsetConfigurationDialogContent
          configurableSets={configurableSets}
          onSubmitSuccess={onSubmitSuccess}
        />
      </BaseDialog>,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser() },
          userGameAchievementSetPreferences: {},
        },
      },
    );

    // ASSERT
    expect(screen.getByText('Bonus Set')).toBeVisible();
    expect(screen.getByText('Challenge Set')).toBeVisible();
  });

  it('given there is somehow no authenticated user, does not crash', () => {
    // ARRANGE
    const { container } = render(
      <BaseDialog open={true}>
        <SubsetConfigurationDialogContent configurableSets={[]} onSubmitSuccess={vi.fn()} />
      </BaseDialog>,
      {
        pageProps: {
          auth: null,
          userGameAchievementSetPreferences: {},
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
    expect(screen.getByText(/globally opted in to all subsets/i)).toBeVisible();
  });
});
