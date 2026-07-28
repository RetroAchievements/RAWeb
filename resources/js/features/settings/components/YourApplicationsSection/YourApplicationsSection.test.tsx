import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createOAuthClient, createZiggyProps } from '@/test/factories';

import { YourApplicationsSection } from './YourApplicationsSection';

describe('Component: YourApplicationsSection', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<YourApplicationsSection />, {
      pageProps: {
        can: { createOAuthClients: true },
        oauthApplicationLimit: 5,
        oauthApplications: [],
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user has no applications, explains the section and offers registration', () => {
    // ARRANGE
    render(<YourApplicationsSection />, {
      pageProps: {
        can: { createOAuthClients: true },
        oauthApplicationLimit: 5,
        oauthApplications: [],
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    const registerButton = screen.getByRole('button', {
      name: 'Register application',
    });

    // ASSERT
    expect(registerButton).toBeEnabled();
    expect(
      screen.getByText('Build and manage OAuth applications for the RetroAchievements community.'),
    ).toBeVisible();
  });

  it('given the user has applications, lists them with their client IDs', () => {
    // ARRANGE
    render(<YourApplicationsSection />, {
      pageProps: {
        can: { createOAuthClients: true },
        oauthApplicationLimit: 5,
        oauthApplications: [createOAuthClient({ id: 'client-123', name: 'My Integration' })],
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    const applicationName = screen.getByText('My Integration');

    // ASSERT
    expect(applicationName).toBeVisible();
    expect(screen.getByText('client-123')).toBeVisible();
    expect(screen.getByRole('button', { name: 'Manage' })).toBeVisible();
    expect(screen.getByRole('button', { name: 'Deactivate' })).toBeVisible();
  });

  it('given the user is at their application quota, disables registration and explains why', () => {
    // ARRANGE
    render(<YourApplicationsSection />, {
      pageProps: {
        can: { createOAuthClients: true },
        oauthApplicationLimit: 2,
        oauthApplications: [createOAuthClient(), createOAuthClient()],
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    const registerButton = screen.getByRole('button', {
      name: 'Register application',
    });

    // ASSERT
    expect(registerButton).toBeDisabled();
    expect(screen.getByText('You can register up to 2 applications.')).toBeVisible();
  });

  it('given the user cannot create applications due to an unverified email, tells them to verify their email address', () => {
    // ARRANGE
    render(<YourApplicationsSection />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ isEmailVerified: false }) },
        can: { createOAuthClients: false },
        oauthApplicationLimit: 5,
        oauthApplications: [],
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    const verifyEmailMessage = screen.getByText(
      'Verify your email address to register an application.',
    );

    // ASSERT
    expect(verifyEmailMessage).toBeVisible();
    expect(screen.queryByRole('button', { name: 'Register application' })).not.toBeInTheDocument();
  });

  it('given a verified user cannot create applications, tells them their account is too new', () => {
    // ARRANGE
    render(<YourApplicationsSection />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ isEmailVerified: true }) },
        can: { createOAuthClients: false },
        oauthApplicationLimit: 5,
        oauthApplications: [],
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    const tooNewMessage = screen.getByText('Your account is too new to register an application.');

    // ASSERT
    expect(tooNewMessage).toBeVisible();
    expect(screen.queryByRole('button', { name: 'Register application' })).not.toBeInTheDocument();
  });
});
