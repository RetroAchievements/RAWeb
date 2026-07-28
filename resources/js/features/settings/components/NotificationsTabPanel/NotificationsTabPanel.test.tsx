import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';

import { NotificationsTabPanel } from './NotificationsTabPanel';

describe('Component: NotificationsTabPanel', () => {
  it('renders the notifications settings card', () => {
    // ARRANGE
    render<App.Community.Data.UserSettingsPageProps>(<NotificationsTabPanel />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ preferencesBitfield: 0 }) },
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: 'Notifications' })).toBeVisible();
  });
});
