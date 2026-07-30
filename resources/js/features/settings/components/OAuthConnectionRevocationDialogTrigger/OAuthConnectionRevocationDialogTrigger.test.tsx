import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { render, screen, waitFor } from '@/test';
import { createConnectedOAuthApplication, createZiggyProps } from '@/test/factories';

import { OAuthConnectionRevocationDialogTrigger } from './OAuthConnectionRevocationDialogTrigger';

describe('Component: OAuthConnectionRevocationDialogTrigger', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.spyOn(router, 'reload').mockImplementation(vi.fn());
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <OAuthConnectionRevocationDialogTrigger application={createConnectedOAuthApplication()} />,
      { pageProps: { ziggy: createZiggyProps() } },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user opens the dialog, names the application and explains the immediate loss of access', async () => {
    // ARRANGE
    render(
      <OAuthConnectionRevocationDialogTrigger
        application={createConnectedOAuthApplication({ name: 'My Connected App' })}
      />,
      { pageProps: { ziggy: createZiggyProps() } },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Revoke' }));

    // ASSERT
    expect(screen.getByRole('dialog')).toBeVisible();
    expect(screen.getByText('Revoke My Connected App?')).toBeVisible();
    expect(
      screen.getByText(
        'This application will immediately lose access to your RetroAchievements account.',
      ),
    ).toBeVisible();
    expect(screen.getByRole('button', { name: 'Cancel' })).toBeVisible();
    expect(screen.getByRole('button', { name: 'Revoke application' })).toBeVisible();
  });

  it('given the user confirms the revocation, refreshes the connected application list', async () => {
    // ARRANGE
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ data: { success: true } });

    render(
      <OAuthConnectionRevocationDialogTrigger application={createConnectedOAuthApplication()} />,
      {
        pageProps: { ziggy: createZiggyProps() },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Revoke' }));
    await userEvent.click(screen.getByRole('button', { name: 'Revoke application' }));

    // ASSERT
    await waitFor(() => {
      expect(router.reload).toHaveBeenCalledWith({
        only: ['connectedOAuthApplications'],
      });
    });
    expect(deleteSpy).toHaveBeenCalledOnce();
  });

  it('given the revocation fails, keeps the dialog open and does not refresh the list', async () => {
    // ARRANGE
    vi.spyOn(axios, 'delete').mockRejectedValueOnce(new Error('Network error'));

    render(
      <OAuthConnectionRevocationDialogTrigger application={createConnectedOAuthApplication()} />,
      {
        pageProps: { ziggy: createZiggyProps() },
      },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Revoke' }));
    await userEvent.click(screen.getByRole('button', { name: 'Revoke application' }));

    // ASSERT
    expect(await screen.findByText('Something went wrong.')).toBeVisible();
    expect(screen.getByRole('dialog')).toBeVisible();
    expect(router.reload).not.toHaveBeenCalled();
  });
});
