import { resetIntersectionMocking } from 'react-intersection-observer/test-utils';

import { createAuthenticatedUser } from '@/common/models';
import { render } from '@/test';
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
});
