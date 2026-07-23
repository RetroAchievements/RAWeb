import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import {
  createConnectedOAuthApplication,
  createOAuthClient,
  createUser,
  createZiggyProps,
} from '@/test/factories';

import { ApplicationsTabPanel } from './ApplicationsTabPanel';

describe('Component: ApplicationsTabPanel', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ApplicationsTabPanel />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        can: { manipulateApiKeys: true, viewAnyOAuthClients: false },
        userSettings: createUser(),
        connectedOAuthApplications: [],
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders the API key card for a verified user who can manage keys', () => {
    // ARRANGE
    render(<ApplicationsTabPanel />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        can: { manipulateApiKeys: true, viewAnyOAuthClients: false },
        userSettings: createUser(),
        connectedOAuthApplications: [],
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    const apiAccessHeading = screen.getByRole('heading', {
      name: 'API Access',
    });

    // ASSERT
    expect(apiAccessHeading).toBeVisible();
  });

  it('given the user cannot manage API keys, tells them to verify their email address', () => {
    // ARRANGE
    render(<ApplicationsTabPanel />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        can: { manipulateApiKeys: false, viewAnyOAuthClients: false },
        userSettings: createUser(),
        connectedOAuthApplications: [],
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    const emptyState = screen.getByText('Verify your email address to manage API keys.');

    // ASSERT
    expect(emptyState).toBeVisible();
    expect(screen.queryByRole('heading', { name: 'API Access' })).not.toBeInTheDocument();
  });

  it('given the user can view OAuth clients, shows API access before developer tools', () => {
    // ARRANGE
    render(<ApplicationsTabPanel />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        can: {
          createOAuthClients: true,
          manipulateApiKeys: true,
          viewAnyOAuthClients: true,
        },
        userSettings: createUser(),
        oauthApplicationLimit: 5,
        oauthApplications: [createOAuthClient()],
        connectedOAuthApplications: [],
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    const headings = screen.getAllByRole('heading').map((heading) => heading.textContent);

    // ASSERT
    expect(headings).toEqual(['API Access', 'For Developers']);
    expect(screen.getByRole('button', { name: 'Register application' })).toBeVisible();
  });

  it('given the user cannot view OAuth clients, hides the developer tools section', () => {
    // ARRANGE
    render(<ApplicationsTabPanel />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        can: { manipulateApiKeys: true, viewAnyOAuthClients: false },
        userSettings: createUser(),
        connectedOAuthApplications: [],
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    const developerHeading = screen.queryByRole('heading', {
      name: 'For Developers',
    });

    // ASSERT
    expect(developerHeading).not.toBeInTheDocument();
    expect(screen.getByRole('heading', { name: 'API Access' })).toBeVisible();
  });

  it('given OAuth is disabled but the user has connections, keeps them available for revocation', () => {
    // ARRANGE
    render(<ApplicationsTabPanel />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        can: { manipulateApiKeys: true, viewAnyOAuthClients: false },
        userSettings: createUser(),
        connectedOAuthApplications: [createConnectedOAuthApplication({ name: 'Connected Client' })],
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    const revokeButton = screen.getByRole('button', { name: 'Revoke' });

    // ASSERT
    expect(screen.getByRole('heading', { name: 'Connected Applications' })).toBeVisible();
    expect(screen.getByText('Connected Client')).toBeVisible();
    expect(revokeButton).toBeVisible();
    expect(screen.queryByRole('heading', { name: 'For Developers' })).not.toBeInTheDocument();
  });
});
