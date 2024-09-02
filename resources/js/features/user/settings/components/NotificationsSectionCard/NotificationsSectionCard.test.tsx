import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { createAuthenticatedUser } from '@/common/models';
import { convertObjectToWebsitePrefs } from '@/common/utils/convertObjectToWebsitePrefs';
import { UserPreference } from '@/common/utils/generatedAppConstants';
import { render, screen } from '@/test';
import { createUser } from '@/test/factories';

import { NotificationsSectionCard } from './NotificationsSectionCard';

describe('Component: NotificationsSectionCard', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const userSettings = createUser();

    const { container } = render<App.Community.Data.UserSettingsPageProps>(
      <NotificationsSectionCard
        currentWebsitePrefs={userSettings.websitePrefs ?? 0}
        onUpdateWebsitePrefs={vi.fn()}
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
      <NotificationsSectionCard
        currentWebsitePrefs={mockWebsitePrefs}
        onUpdateWebsitePrefs={vi.fn()}
      />,
      {
        pageProps: {
          can: {},
          userSettings: createUser(),
          auth: { user: createAuthenticatedUser({ websitePrefs: mockWebsitePrefs }) },
        },
      },
    );

    // ASSERT
    expect(screen.getByTestId(`email-checkbox-comments-on-my-activity`)).toBeChecked();
    expect(screen.getByTestId(`site-checkbox-comments-on-my-activity`)).toBeChecked();
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
      <NotificationsSectionCard
        currentWebsitePrefs={mockWebsitePrefs}
        onUpdateWebsitePrefs={vi.fn()}
      />,
      {
        pageProps: {
          auth: { user: createAuthenticatedUser({ websitePrefs: mockWebsitePrefs }) },
        },
      },
    );

    // ACT
    await userEvent.click(screen.getByTestId(`email-checkbox-someone-follows-me`));
    await userEvent.click(screen.getByRole('button', { name: /update/i }));

    // ASSERT
    expect(putSpy).toHaveBeenCalledWith(route('settings.preferences.update'), {
      websitePrefs: 401,
    });
  });
});
