import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { createAuthenticatedUser } from '@/common/models';
import { convertObjectToWebsitePrefs } from '@/common/utils/convertObjectToWebsitePrefs';
import { UserPreference } from '@/common/utils/generatedAppConstants';
import { render, screen } from '@/test';
import { createUser } from '@/test/factories';

import { NotificationsSectionCard } from './NotificationsSectionCard';

describe('Component: NotificationsSectionCard', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.spyOn(router, 'reload').mockImplementation(vi.fn());
  });

  it('renders without crashing', () => {
    // ARRANGE
    const userSettings = createUser();

    const { container } = render<App.Community.Data.UserSettingsPageProps>(
      <NotificationsSectionCard
        currentPreferencesBitfield={userSettings.preferencesBitfield ?? 0}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('can correctly initially check the right checkboxes based on the user preferences bit value', () => {
    // ARRANGE
    const mappedPreferences = {
      [UserPreference.EmailOn_ActivityComment]: true,
      [UserPreference.SiteMsgOn_ActivityComment]: true,
      [UserPreference.Site_SuppressMatureContentWarning]: true,
    };

    const mockWebsitePrefs = convertObjectToWebsitePrefs(mappedPreferences);

    render<App.Community.Data.UserSettingsPageProps>(
      <NotificationsSectionCard currentPreferencesBitfield={mockWebsitePrefs} />,
      {
        pageProps: {
          can: {},
          userSettings: createUser(),
          auth: {
            user: createAuthenticatedUser({
              preferencesBitfield: mockWebsitePrefs,
              roles: ['developer'],
            }),
          },
        },
      },
    );

    // ASSERT
    // ... the dev-only checkbox should be visible and checked (EmailOn_ActivityComment = true) ...
    const checkboxes = screen.getAllByRole('checkbox');
    expect(checkboxes[0]).toBeChecked();
  });

  it('given the user submits the form, sends the correct updated websitePrefs bit value to the server', async () => {
    // ARRANGE
    const putSpy = vi.spyOn(axios, 'put').mockResolvedValueOnce({ success: true });

    const mappedPreferences = {
      [UserPreference.EmailOn_ActivityComment]: true,
      [UserPreference.SiteMsgOn_ActivityComment]: true,
      [UserPreference.Site_SuppressMatureContentWarning]: true,
    };

    const mockWebsitePrefs = convertObjectToWebsitePrefs(mappedPreferences);

    render<App.Community.Data.UserSettingsPageProps>(
      <NotificationsSectionCard currentPreferencesBitfield={mockWebsitePrefs} />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser({ preferencesBitfield: mockWebsitePrefs }) },
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByTestId(`email-checkbox-someone-follows-me`));
    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(putSpy).toHaveBeenCalledWith(route('api.settings.preferences.update'), {
      preferencesBitfield: 401,
    });
  });

  it('shows developer-only notification setting for users with developer role', () => {
    // ARRANGE
    render<App.Community.Data.UserSettingsPageProps>(
      <NotificationsSectionCard currentPreferencesBitfield={0} />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
        },
      },
    );

    // ASSERT
    expect(
      screen.getAllByText(
        /Someone comments on any achievement in games where I've subscribed to all achievement comments/i,
      )[0],
    ).toBeVisible();
  });

  it('shows developer-only notification setting for users with developer-junior role', () => {
    // ARRANGE
    render<App.Community.Data.UserSettingsPageProps>(
      <NotificationsSectionCard currentPreferencesBitfield={0} />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser({ roles: ['developer-junior'] }) },
        },
      },
    );

    // ASSERT
    expect(
      screen.getAllByText(
        /Someone comments on any achievement in games where I've subscribed to all achievement comments/i,
      )[0],
    ).toBeVisible();
  });

  it('hides developer-only notification setting for non-developer users', () => {
    // ARRANGE
    render<App.Community.Data.UserSettingsPageProps>(
      <NotificationsSectionCard currentPreferencesBitfield={0} />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser({ roles: [] }) },
        },
      },
    );

    // ASSERT
    expect(
      screen.queryByText(
        /Someone comments on any achievement in games where I've subscribed to all achievement comments/i,
      ),
    ).not.toBeInTheDocument();
  });
});
