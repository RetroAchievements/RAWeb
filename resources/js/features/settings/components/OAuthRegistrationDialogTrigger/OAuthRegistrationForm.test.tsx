import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { render, screen, waitFor } from '@/test';
import { createZiggyProps } from '@/test/factories';

import { OAuthRegistrationForm } from './OAuthRegistrationForm';

describe('Component: OAuthRegistrationForm', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.spyOn(router, 'reload').mockImplementation(vi.fn());
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<OAuthRegistrationForm onSuccess={vi.fn()} />, {
      pageProps: { ziggy: createZiggyProps() },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders accessible labeled controls', () => {
    // ARRANGE
    render(<OAuthRegistrationForm onSuccess={vi.fn()} />, {
      pageProps: { ziggy: createZiggyProps() },
    });

    // ACT
    const applicationNameInput = screen.getByLabelText('Application name');

    // ASSERT
    expect(applicationNameInput).toBeVisible();
    expect(screen.getByLabelText('Redirect URI')).toBeVisible();
    expect(screen.getByLabelText('Public client (PKCE)')).toBeVisible();
    expect(screen.getByPlaceholderText('My RetroAchievements App')).toBeVisible();
    expect(screen.getByPlaceholderText('https://example.com/oauth/callback')).toBeVisible();
    expect(
      screen.getByText(
        'For browser, mobile, CLI, and other applications that cannot securely store a client secret.',
      ),
    ).toBeVisible();
  });

  it('given the required values are empty, does not submit', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post');

    render(<OAuthRegistrationForm onSuccess={vi.fn()} />, {
      pageProps: { ziggy: createZiggyProps() },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Register application' }));

    // ASSERT
    expect(postSpy).not.toHaveBeenCalled();
    expect(screen.getByLabelText('Application name')).toBeInvalid();
    expect(screen.getByLabelText('Redirect URI')).toBeInvalid();
  });

  it('given the application name is too short, shows a schema error', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post');

    render(<OAuthRegistrationForm onSuccess={vi.fn()} />, {
      pageProps: { ziggy: createZiggyProps() },
    });

    // ACT
    await userEvent.type(screen.getByLabelText('Application name'), 'ab');
    await userEvent.type(screen.getByLabelText('Redirect URI'), 'https://example.com/callback');
    await userEvent.click(screen.getByRole('button', { name: 'Register application' }));

    // ASSERT
    expect(postSpy).not.toHaveBeenCalled();
    expect(await screen.findByText('Must be at least 3 characters.')).toBeVisible();
  });

  it('given a redirect URI the server would reject, blocks the request before it is sent', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post');

    render(<OAuthRegistrationForm onSuccess={vi.fn()} />, {
      pageProps: { ziggy: createZiggyProps() },
    });

    // ACT
    await userEvent.type(screen.getByLabelText('Application name'), 'My Integration');
    await userEvent.type(screen.getByLabelText('Redirect URI'), 'http://evil.example.com/callback');
    await userEvent.click(screen.getByRole('button', { name: 'Register application' }));

    // ASSERT
    expect(
      await screen.findByText('Enter a secure redirect URI without wildcards or fragments.'),
    ).toBeVisible();
    expect(postSpy).not.toHaveBeenCalled();
  });

  it('given valid values, submits them and returns the credentials', async () => {
    // ARRANGE
    const credentials = { id: 'client-123', secret: 'secret-123' };
    const onSuccess = vi.fn();
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: credentials });

    render(<OAuthRegistrationForm onSuccess={onSuccess} />, {
      pageProps: { ziggy: createZiggyProps() },
    });

    // ACT
    await userEvent.type(screen.getByLabelText('Application name'), 'My Integration');
    await userEvent.type(screen.getByLabelText('Redirect URI'), 'https://example.com/callback');
    await userEvent.click(screen.getByLabelText('Public client (PKCE)'));
    await userEvent.click(screen.getByRole('button', { name: 'Register application' }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledWith(route('api.settings.applications.store'), {
        enableDeviceFlow: true,
        name: 'My Integration',
        redirectUris: ['https://example.com/callback'],
        type: 'public',
      });
    });
    expect(onSuccess).toHaveBeenCalledWith(credentials);
  });

  it('given the server rejects the registration, surfaces the server message', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockRejectedValueOnce({
      response: {
        status: 422,
        data: {
          message: 'You can only have 5 active applications. Deactivate one to register another.',
        },
      },
    });

    render(<OAuthRegistrationForm onSuccess={vi.fn()} />, {
      pageProps: { ziggy: createZiggyProps() },
    });

    // ACT
    await userEvent.type(screen.getByLabelText('Application name'), 'My Integration');
    await userEvent.type(screen.getByLabelText('Redirect URI'), 'https://example.com/callback');
    await userEvent.click(screen.getByRole('button', { name: 'Register application' }));

    // ASSERT
    expect(
      await screen.findByText(
        'You can only have 5 active applications. Deactivate one to register another.',
      ),
    ).toBeVisible();
  });
});
