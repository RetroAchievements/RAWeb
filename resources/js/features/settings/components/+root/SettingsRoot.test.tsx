import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { resetIntersectionMocking } from 'react-intersection-observer/test-utils';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createUser } from '@/test/factories';

import { SettingsRoot } from './SettingsRoot';

// Suppress setState() warnings that only happen in JSDOM.
console.error = vi.fn();

describe('Component: SettingsRoot', () => {
  afterEach(() => {
    resetIntersectionMocking();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Community.Data.UserSettingsPageProps>(<SettingsRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ websitePrefs: 139687 }) },
        userSettings: createUser(),
        can: { updateMotto: true },
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('allows the user to update their website preferences', async () => {
    // ARRANGE
    const putSpy = vi.spyOn(axios, 'put').mockResolvedValueOnce({ success: true });

    render<App.Community.Data.UserSettingsPageProps>(<SettingsRoot />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ websitePrefs: 139471 }) },
        userSettings: createUser(),
        can: { updateMotto: true },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('switch', { name: /only people i follow/i }));
    await userEvent.click(screen.getByTestId('Preferences-submit'));

    // ASSERT
    expect(putSpy).toHaveBeenCalledWith(route('api.settings.preferences.update'), {
      websitePrefs: 8399,
    });
  });

  it('given the user is muted and is email verified, does not show the change username section', async () => {
    // ARRANGE
    render<App.Community.Data.UserSettingsPageProps>(<SettingsRoot />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            websitePrefs: 139687,
            isMuted: true,
            isEmailVerified: true,
          }),
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
    render<App.Community.Data.UserSettingsPageProps>(<SettingsRoot />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            websitePrefs: 139687,
            isMuted: false,
            isEmailVerified: true,
          }),
        },
        userSettings: createUser(),
        can: { updateMotto: true },
      },
    });

    // ASSERT
    expect(screen.getByText(/change username/i)).toBeVisible();
  });

  it('given the user is not muted and is not email verified, does not show the change username change', () => {
    // ARRANGE
    render<App.Community.Data.UserSettingsPageProps>(<SettingsRoot />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            websitePrefs: 139687,
            isMuted: false,
            isEmailVerified: false,
          }),
        },
        userSettings: createUser(),
        can: { updateMotto: true },
      },
    });

    // ASSERT
    expect(screen.queryByText(/change username/i)).not.toBeInTheDocument();
  });
});
