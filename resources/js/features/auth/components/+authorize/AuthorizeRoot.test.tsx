/* eslint-disable testing-library/no-container */

import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';

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
      },
    });

    // ASSERT
    expect(screen.getByText(/test app wants to access your account/i)).toBeVisible();

    expect(screen.getByText(/this will allow test app to:/i)).toBeVisible();
    expect(screen.getByText(/access your profile information/i)).toBeVisible();
    expect(screen.getByText(/make api calls on your behalf/i)).toBeVisible();

    expect(screen.getByText(/currently signed in as/i)).toBeVisible();
    expect(screen.getByText('Scott')).toBeVisible();
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
      },
    });

    // ASSERT
    const denyForm = screen.getByRole('button', { name: /deny/i }).closest('form');
    const approveForm = screen.getByRole('button', { name: /authorize/i }).closest('form');

    expect(denyForm?.querySelector('input[name="state"]')).toHaveValue('');
    expect(approveForm?.querySelector('input[name="state"]')).toHaveValue('');
  });
});
