import { BaseDialog } from '@/common/components/+vendor/BaseDialog';
import { createAuthenticatedUser } from '@/common/models';
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

  it('displays the instruction message', () => {
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
      screen.getByText(/select which sets will be active when you play the game/i),
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
    expect(screen.getByText(/select which sets/i)).toBeVisible();
  });
});
