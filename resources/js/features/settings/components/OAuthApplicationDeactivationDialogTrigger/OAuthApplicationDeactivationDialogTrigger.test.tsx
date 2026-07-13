import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { render, screen, waitFor } from '@/test';
import { createOAuthClient, createZiggyProps } from '@/test/factories';

import { OAuthApplicationDeactivationDialogTrigger } from './OAuthApplicationDeactivationDialogTrigger';

describe('Component: OAuthApplicationDeactivationDialogTrigger', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.spyOn(router, 'reload').mockImplementation(vi.fn());
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <OAuthApplicationDeactivationDialogTrigger application={createOAuthClient()} />,
      { pageProps: { ziggy: createZiggyProps() } },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user opens the dialog, names the application and explains the destructive consequence', async () => {
    // ARRANGE
    render(
      <OAuthApplicationDeactivationDialogTrigger
        application={createOAuthClient({ name: 'My Integration' })}
      />,
      { pageProps: { ziggy: createZiggyProps() } },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Deactivate' }));

    // ASSERT
    expect(screen.getByRole('dialog')).toBeVisible();
    expect(screen.getByText('Deactivate My Integration?')).toBeVisible();
    expect(
      screen.getByText(
        'Every access and refresh token issued to this application will be revoked. This cannot be undone.',
      ),
    ).toBeVisible();
  });

  it('given the user confirms the deactivation, refreshes the application list', async () => {
    // ARRANGE
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ data: { success: true } });

    render(<OAuthApplicationDeactivationDialogTrigger application={createOAuthClient()} />, {
      pageProps: { ziggy: createZiggyProps() },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Deactivate' }));
    await userEvent.click(screen.getByRole('button', { name: 'Deactivate application' }));

    // ASSERT
    await waitFor(() => {
      expect(router.reload).toHaveBeenCalledWith({
        only: ['oauthApplications'],
      });
    });
    expect(deleteSpy).toHaveBeenCalledOnce();
  });

  it('given the deactivation fails, keeps the dialog open and does not refresh the list', async () => {
    // ARRANGE
    vi.spyOn(axios, 'delete').mockRejectedValueOnce(new Error('Network error'));

    render(<OAuthApplicationDeactivationDialogTrigger application={createOAuthClient()} />, {
      pageProps: { ziggy: createZiggyProps() },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Deactivate' }));
    await userEvent.click(screen.getByRole('button', { name: 'Deactivate application' }));

    // ASSERT
    expect(await screen.findByText('Something went wrong.')).toBeVisible();
    expect(screen.getByRole('dialog')).toBeVisible();
    expect(router.reload).not.toHaveBeenCalled();
  });
});
