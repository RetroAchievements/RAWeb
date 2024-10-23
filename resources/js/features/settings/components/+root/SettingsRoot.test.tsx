import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { resetIntersectionMocking } from 'react-intersection-observer/test-utils';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createUser } from '@/test/factories';

import { SettingsRoot } from './SettingsRoot';

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
});
