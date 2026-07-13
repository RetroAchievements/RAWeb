import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import * as ReactUseModule from 'react-use';

import { render, screen, waitFor } from '@/test';
import { createZiggyProps } from '@/test/factories';

import { OAuthRegistrationDialogTrigger } from './OAuthRegistrationDialogTrigger';

describe('Component: OAuthRegistrationDialogTrigger', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.spyOn(router, 'reload').mockImplementation(vi.fn());
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<OAuthRegistrationDialogTrigger />, {
      pageProps: { ziggy: createZiggyProps() },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a confidential application was registered, presents copy-first credentials and prevents premature dismissal', async () => {
    // ARRANGE
    const copyToClipboardSpy = vi.fn();
    vi.spyOn(ReactUseModule, 'useCopyToClipboard').mockReturnValue([
      null as any,
      copyToClipboardSpy,
    ]);
    vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: {
        id: '019f586e-5352-70a9-abca-74f6a8fe2191',
        secret: '2iCu04BcTGySf07b7wzbLnXDhzc4vbsJyh',
      },
    });

    render(<OAuthRegistrationDialogTrigger />, {
      pageProps: { ziggy: createZiggyProps() },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Register application' }));
    await userEvent.type(screen.getByLabelText('Application name'), 'My Integration');
    await userEvent.type(screen.getByLabelText('Redirect URI'), 'https://example.com/callback');
    await userEvent.click(screen.getByRole('button', { name: 'Register application' }));
    await screen.findByRole('heading', { name: 'Save your credentials' });
    await userEvent.keyboard('{Escape}');
    await userEvent.click(screen.getByRole('button', { name: 'Copy client ID' }));
    await userEvent.click(screen.getByRole('button', { name: 'Copy client secret' }));

    // ASSERT
    expect(screen.getByRole('heading', { name: 'Save your credentials' })).toBeVisible();
    expect(screen.getByText('019f58...fe2191')).toBeVisible();
    expect(screen.getByText('2iCu04...vbsJyh')).toBeVisible();
    expect(screen.getByRole('button', { name: "I've saved these" })).toBeVisible();
    expect(screen.getByRole('dialog')).toBeVisible();
    expect(copyToClipboardSpy).toHaveBeenCalledWith('019f586e-5352-70a9-abca-74f6a8fe2191');
    expect(copyToClipboardSpy).toHaveBeenCalledWith('2iCu04BcTGySf07b7wzbLnXDhzc4vbsJyh');
  });

  it('given a public application was registered, does not show an irrecoverable-secret warning', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: {
        id: '019f586e-5352-70a9-abca-74f6a8fe2191',
        secret: null,
      },
    });

    render(<OAuthRegistrationDialogTrigger />, {
      pageProps: { ziggy: createZiggyProps() },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Register application' }));
    await userEvent.type(screen.getByLabelText('Application name'), 'My Public Integration');
    await userEvent.type(screen.getByLabelText('Redirect URI'), 'my-app://callback');
    await userEvent.click(screen.getByLabelText('Public client (PKCE)'));
    await userEvent.click(screen.getByRole('button', { name: 'Register application' }));

    // ASSERT
    expect(await screen.findByRole('heading', { name: 'Your application is ready' })).toBeVisible();
    expect(
      screen.getByText('Copy the client ID to finish configuring your application.'),
    ).toBeVisible();
    expect(screen.queryByText(/won't be able to see/i)).not.toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Done' })).toBeVisible();
  });

  it('given no credentials have been created yet, allows dismissal', async () => {
    // ARRANGE
    render(<OAuthRegistrationDialogTrigger />, {
      pageProps: { ziggy: createZiggyProps() },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Register application' }));
    await userEvent.keyboard('{Escape}');

    // ASSERT
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
  });

  it('given an application was registered, refreshes the application list', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: { id: 'client-123', secret: 'secret-123' },
    });

    render(<OAuthRegistrationDialogTrigger />, {
      pageProps: { ziggy: createZiggyProps() },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Register application' }));
    await userEvent.type(screen.getByLabelText('Application name'), 'My Integration');
    await userEvent.type(screen.getByLabelText('Redirect URI'), 'https://example.com/callback');
    await userEvent.click(screen.getByRole('button', { name: 'Register application' }));
    await userEvent.click(await screen.findByRole('button', { name: "I've saved these" }));

    // ASSERT
    await waitFor(() => {
      expect(router.reload).toHaveBeenCalledWith({
        only: ['oauthApplications'],
      });
    });
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
  });
});
