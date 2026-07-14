import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createUser } from '@/test/factories';

import { AccountTabPanel } from './AccountTabPanel';

describe('Component: AccountTabPanel', () => {
  it('given the user is muted and is email verified, does not show the change username section', () => {
    // ARRANGE
    render<App.Community.Data.UserSettingsPageProps>(<AccountTabPanel />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({ isMuted: true, isEmailVerified: true }),
        },
        userSettings: createUser(),
        can: { updateMotto: true },
      },
    });

    // ASSERT
    expect(screen.queryByText(/change username/i)).not.toBeInTheDocument();
  });

  it('given the user is not muted and is email verified, shows the change username section', () => {
    // ARRANGE
    render<App.Community.Data.UserSettingsPageProps>(<AccountTabPanel />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({ isMuted: false, isEmailVerified: true }),
        },
        userSettings: createUser(),
        can: { updateMotto: true },
      },
    });

    // ASSERT
    expect(screen.getByText(/change username/i)).toBeVisible();
  });

  it('given the user is not muted and is not email verified, does not show the change username section', () => {
    // ARRANGE
    render<App.Community.Data.UserSettingsPageProps>(<AccountTabPanel />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({ isMuted: false, isEmailVerified: false }),
        },
        userSettings: createUser(),
        can: { updateMotto: true },
      },
    });

    // ASSERT
    expect(screen.queryByText(/change username/i)).not.toBeInTheDocument();
  });

  it('given the user has permission to reset their entire account, shows the reset entire account section', () => {
    // ARRANGE
    render<App.Community.Data.UserSettingsPageProps>(<AccountTabPanel />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        userSettings: createUser(),
        can: { updateMotto: true, resetEntireAccount: true },
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /reset entire account/i })).toBeVisible();
  });

  it('given the user lacks permission to reset their entire account, does not show the reset entire account section', () => {
    // ARRANGE
    render<App.Community.Data.UserSettingsPageProps>(<AccountTabPanel />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        userSettings: createUser(),
        can: { updateMotto: true, resetEntireAccount: false },
      },
    });

    // ASSERT
    expect(screen.queryByText(/reset entire account/i)).not.toBeInTheDocument();
  });
});
