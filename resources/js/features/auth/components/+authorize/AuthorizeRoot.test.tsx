/* eslint-disable testing-library/no-container */

import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createUser } from '@/test/factories';

import { AuthorizeRoot } from './AuthorizeRoot';

vi.mock('../OAuthPageLayout', () => ({
  OAuthPageLayout: ({ children }: any) => <div data-testid="oauth-page-layout">{children}</div>,
}));

describe('Component: AuthorizeRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<AuthorizeRoot variant="app" />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            displayName: 'John Doe',
            avatarUrl: 'https://example.com/avatar.jpg',
          }),
        },
        authToken: 'test-auth-token',
        client: {
          id: 'client-123',
          name: 'Test App',
        },
        csrfToken: 'csrf-token-123',
        request: {
          state: 'request-state-123',
        },
        scopes: ['data:read'],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the correct copy', () => {
    // ARRANGE
    render(<AuthorizeRoot variant="app" />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({ displayName: 'Scott' }),
        },
        authToken: 'test-auth-token',
        client: {
          id: 'client-123',
          name: 'Test App',
        },
        csrfToken: 'csrf-token-123',
        request: {
          state: 'request-state-123',
        },
        scopes: ['data:read'],
      },
    });

    // ASSERT
    expect(screen.getByText(/test app wants to access your account/i)).toBeVisible();

    expect(screen.getByText(/this will let it:/i)).toBeVisible();
    expect(screen.queryByText(/this will allow test app to:/i)).not.toBeInTheDocument();
    expect(screen.getByText(/view publicly visible retroachievements data/i)).toBeVisible();

    expect(screen.getByText(/currently signed in as/i)).toBeVisible();
    expect(screen.getByText('Scott')).toBeVisible();
  });

  it('given the client has an owner, names them as the registrant', () => {
    // ARRANGE
    render(<AuthorizeRoot variant="app" />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
        authToken: 'test-auth-token',
        client: {
          id: 'client-123',
          name: 'Test App',
          owner: createUser({ displayName: 'Jamiras' }),
        },
        csrfToken: 'csrf-token-123',
        request: {
          state: 'request-state-123',
        },
        scopes: ['data:read'],
      },
    });

    // ASSERT
    expect(screen.getByText(/registered by/i)).toBeVisible();

    const ownerLink = screen.getByRole('link', { name: 'Jamiras' });

    expect(ownerLink).toBeVisible();
    expect(ownerLink).toHaveAttribute('href', expect.stringContaining('user.show'));

    expect(ownerLink).toHaveAttribute('target', '_blank');
    expect(ownerLink).toHaveAttribute('rel', 'noopener');
  });

  it('given the client has no owner, does not crash', () => {
    // ARRANGE
    render(<AuthorizeRoot variant="app" />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
        authToken: 'test-auth-token',
        client: {
          id: 'client-123',
          name: 'Test App',
          owner: null, // !!
        },
        csrfToken: 'csrf-token-123',
        request: {
          state: 'request-state-123',
        },
        scopes: ['data:read'],
      },
    });

    // ASSERT
    expect(screen.queryByText(/registered by/i)).not.toBeInTheDocument();
  });

  it('translates known scopes and falls back to the identifier for unknown ones', () => {
    // ARRANGE
    render(<AuthorizeRoot variant="app" />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
        authToken: 'test-auth-token',
        client: {
          id: 'client-123',
          name: 'Test App',
        },
        csrfToken: 'csrf-token-123',
        request: {
          state: 'request-state-123',
        },
        scopes: ['data:read', 'data:mystery'],
      },
    });

    // ASSERT
    expect(screen.getByText('View publicly visible RetroAchievements data')).toBeVisible();
    expect(screen.getByText('data:mystery')).toBeVisible();
  });

  it('given the follows scope, renders its consent copy instead of the identifier', () => {
    // ARRANGE
    render(<AuthorizeRoot variant="app" />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
        authToken: 'test-auth-token',
        client: {
          id: 'client-123',
          name: 'Test App',
        },
        csrfToken: 'csrf-token-123',
        request: {
          state: 'request-state-123',
        },
        scopes: ['follows:read'],
      },
    });

    // ASSERT
    expect(
      screen.getByText('View the people you follow and the people who follow you'),
    ).toBeVisible();
    expect(screen.queryByText('follows:read')).not.toBeInTheDocument();
  });

  it('given the game lists scope, renders its consent copy instead of the identifier', () => {
    // ARRANGE
    render(<AuthorizeRoot variant="app" />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
        authToken: 'test-auth-token',
        client: {
          id: 'client-123',
          name: 'Test App',
        },
        csrfToken: 'csrf-token-123',
        request: {
          state: 'request-state-123',
        },
        scopes: ['game-lists:read'],
      },
    });

    // ASSERT
    expect(
      screen.getByText('View your personal game lists, such as Want to Play and Want to Develop'),
    ).toBeVisible();
    expect(screen.queryByText('game-lists:read')).not.toBeInTheDocument();
  });

  it('given all scopes, renders a distinct line for each', () => {
    // ARRANGE
    render(<AuthorizeRoot variant="app" />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
        authToken: 'test-auth-token',
        client: {
          id: 'client-123',
          name: 'Test App',
        },
        csrfToken: 'csrf-token-123',
        request: {
          state: 'request-state-123',
        },
        scopes: ['data:read', 'follows:read', 'game-lists:read'],
      },
    });

    // ASSERT
    expect(screen.getByText('View publicly visible RetroAchievements data')).toBeVisible();
    expect(
      screen.getByText('View the people you follow and the people who follow you'),
    ).toBeVisible();
    expect(
      screen.getByText('View your personal game lists, such as Want to Play and Want to Develop'),
    ).toBeVisible();
  });

  it('given the device variant, renders the same scope copy as the app variant', () => {
    // ARRANGE
    render(<AuthorizeRoot variant="device" />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
        authToken: 'test-auth-token',
        client: {
          id: 'client-123',
          name: 'Test App',
        },
        csrfToken: 'csrf-token-123',
        request: {
          state: 'request-state-123',
        },
        scopes: ['follows:read', 'game-lists:read'],
      },
    });

    // ASSERT
    expect(
      screen.getByText('View the people you follow and the people who follow you'),
    ).toBeVisible();
    expect(
      screen.getByText('View your personal game lists, such as Want to Play and Want to Develop'),
    ).toBeVisible();
  });

  it('displays both deny and authorize buttons', () => {
    // ARRANGE
    render(<AuthorizeRoot variant="app" />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
        authToken: 'test-auth-token',
        client: {
          id: 'client-123',
          name: 'Test App',
        },
        csrfToken: 'csrf-token-123',
        request: {
          state: 'request-state-123',
        },
        scopes: ['data:read'],
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /deny/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /authorize/i })).toBeVisible();
  });

  it('given the variant is app, uses app-specific routes in forms', () => {
    // ARRANGE
    const { container } = render(<AuthorizeRoot variant="app" />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
        authToken: 'test-auth-token',
        client: {
          id: 'client-123',
          name: 'Test App',
        },
        csrfToken: 'csrf-token-123',
        request: {
          state: 'request-state-123',
        },
        scopes: ['data:read'],
      },
    });

    // ASSERT
    const denyForm = container.querySelector('form[action*="passport.authorizations.deny"]');
    const approveForm = container.querySelector('form[action*="passport.authorizations.approve"]');

    expect(denyForm).toBeVisible();
    expect(approveForm).toBeVisible();
  });

  it('given the variant is device, uses device-specific routes in forms', () => {
    // ARRANGE
    const { container } = render(<AuthorizeRoot variant="device" />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
        authToken: 'test-auth-token',
        client: {
          id: 'client-123',
          name: 'Test App',
        },
        csrfToken: 'csrf-token-123',
        request: {
          state: 'request-state-123',
        },
        scopes: ['data:read'],
      },
    });

    // ASSERT
    const denyForm = container.querySelector('form[action*="passport.device.authorizations.deny"]');
    const approveForm = container.querySelector(
      'form[action*="passport.device.authorizations.approve"]',
    );

    expect(denyForm).toBeVisible();
    expect(approveForm).toBeVisible();
  });

  it('includes all required hidden fields in the deny form', () => {
    // ARRANGE
    render(<AuthorizeRoot variant="app" />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
        authToken: 'test-auth-token',
        client: {
          id: 'client-123',
          name: 'Test App',
        },
        csrfToken: 'csrf-token-123',
        request: {
          state: 'request-state-123',
        },
        scopes: ['data:read'],
      },
    });

    // ASSERT
    const denyForm = screen.getByRole('button', { name: /deny/i }).closest('form');

    expect(denyForm?.querySelector('input[name="_token"]')).toHaveValue('csrf-token-123');
    expect(denyForm?.querySelector('input[name="_method"]')).toHaveValue('DELETE');
    expect(denyForm?.querySelector('input[name="state"]')).toHaveValue('request-state-123');
    expect(denyForm?.querySelector('input[name="client_id"]')).toHaveValue('client-123');
    expect(denyForm?.querySelector('input[name="auth_token"]')).toHaveValue('test-auth-token');
  });

  it('includes all required hidden fields in the approve form', () => {
    // ARRANGE
    render(<AuthorizeRoot variant="app" />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
        authToken: 'test-auth-token',
        client: {
          id: 'client-123',
          name: 'Test App',
        },
        csrfToken: 'csrf-token-123',
        request: {
          state: 'request-state-123',
        },
        scopes: ['data:read'],
      },
    });

    // ASSERT
    const approveForm = screen.getByRole('button', { name: /authorize/i }).closest('form');

    expect(approveForm?.querySelector('input[name="_token"]')).toHaveValue('csrf-token-123');
    expect(approveForm?.querySelector('input[name="state"]')).toHaveValue('request-state-123');
    expect(approveForm?.querySelector('input[name="client_id"]')).toHaveValue('client-123');
    expect(approveForm?.querySelector('input[name="auth_token"]')).toHaveValue('test-auth-token');
  });

  it('given the request state is null, uses an empty string for the state field', () => {
    // ARRANGE
    render(<AuthorizeRoot variant="app" />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
        authToken: 'test-auth-token',
        client: {
          id: 'client-123',
          name: 'Test App',
        },
        csrfToken: 'csrf-token-123',
        request: {
          state: null, // !!
        },
        scopes: ['data:read'],
      },
    });

    // ASSERT
    const denyForm = screen.getByRole('button', { name: /deny/i }).closest('form');
    const approveForm = screen.getByRole('button', { name: /authorize/i }).closest('form');

    expect(denyForm?.querySelector('input[name="state"]')).toHaveValue('');
    expect(approveForm?.querySelector('input[name="state"]')).toHaveValue('');
  });
});
