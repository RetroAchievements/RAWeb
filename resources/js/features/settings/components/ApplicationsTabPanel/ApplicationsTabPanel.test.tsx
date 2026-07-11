import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createUser, createZiggyProps } from '@/test/factories';

import { ApplicationsTabPanel } from './ApplicationsTabPanel';

describe('Component: ApplicationsTabPanel', () => {
  it('renders the API key card for a verified user who can manage keys', () => {
    // ARRANGE
    render(<ApplicationsTabPanel />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ isEmailVerified: true }) },
        can: { manipulateApiKeys: true },
        userSettings: createUser(),
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    const apiAccessHeading = screen.getByRole('heading', { name: 'API Access' });

    // ASSERT
    expect(apiAccessHeading).toBeVisible();
  });

  it('shows an email verification empty state when the user cannot manage keys', () => {
    // ARRANGE
    render(<ApplicationsTabPanel />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ isEmailVerified: false }) },
        can: { manipulateApiKeys: false },
        userSettings: createUser(),
      },
    });

    // ACT
    const emptyState = screen.getByText('Verify your email address to manage API keys.');

    // ASSERT
    expect(emptyState).toBeVisible();
    expect(screen.queryByRole('heading', { name: 'API Access' })).not.toBeInTheDocument();
  });
});
