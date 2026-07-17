import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { render, screen, waitFor, within } from '@/test';
import { createOAuthClient, createZiggyProps } from '@/test/factories';

import { OAuthApplicationManagementDialogTrigger } from './OAuthApplicationManagementDialogTrigger';

describe('Component: OAuthApplicationManagementDialogTrigger', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.spyOn(router, 'reload').mockImplementation(vi.fn());
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <OAuthApplicationManagementDialogTrigger application={createOAuthClient()} />,
      { pageProps: { ziggy: createZiggyProps() } },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user opens the dialog, shows the application values in an accessible form', async () => {
    // ARRANGE
    render(
      <OAuthApplicationManagementDialogTrigger
        application={createOAuthClient({
          name: 'My Integration',
          redirectUris: ['https://example.com/callback'],
        })}
      />,
      { pageProps: { ziggy: createZiggyProps() } },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Manage' }));

    // ASSERT
    expect(screen.getByRole('dialog')).toBeVisible();
    expect(screen.getByLabelText('Application name')).toHaveValue('My Integration');
    expect(screen.getByLabelText('Redirect URI')).toHaveValue('https://example.com/callback');
    expect(screen.getByRole('button', { name: 'Save changes' })).toBeDisabled();
  });

  it('given the user saves a new name, refreshes the application list', async () => {
    // ARRANGE
    const application = createOAuthClient();
    vi.spyOn(axios, 'put').mockResolvedValueOnce({
      data: { ...application, name: 'Updated Integration' },
    });

    render(<OAuthApplicationManagementDialogTrigger application={application} />, {
      pageProps: { ziggy: createZiggyProps() },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Manage' }));
    await userEvent.clear(screen.getByLabelText('Application name'));
    await userEvent.type(screen.getByLabelText('Application name'), 'Updated Integration');
    await userEvent.click(screen.getByRole('button', { name: 'Save changes' }));

    // ASSERT
    await waitFor(() => {
      expect(router.reload).toHaveBeenCalledWith({
        only: ['oauthApplications'],
      });
    });
  });

  it('given the user submits a redirect URI the server would reject, blocks the request', async () => {
    // ARRANGE
    const putSpy = vi.spyOn(axios, 'put');

    render(<OAuthApplicationManagementDialogTrigger application={createOAuthClient()} />, {
      pageProps: { ziggy: createZiggyProps() },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Manage' }));
    await userEvent.clear(screen.getByLabelText('Redirect URI'));
    await userEvent.type(screen.getByLabelText('Redirect URI'), 'http://evil.example.com/callback');
    await userEvent.click(screen.getByRole('button', { name: 'Save changes' }));

    // ASSERT
    expect(
      await screen.findByText('Enter a secure redirect URI without wildcards or fragments.'),
    ).toBeVisible();
    expect(putSpy).not.toHaveBeenCalled();
  });

  it('given an update fails, surfaces the server message and keeps the dialog open', async () => {
    // ARRANGE
    vi.spyOn(axios, 'put').mockRejectedValueOnce({
      response: {
        status: 422,
        data: { message: 'Choose a different application name.' },
      },
    });

    render(<OAuthApplicationManagementDialogTrigger application={createOAuthClient()} />, {
      pageProps: { ziggy: createZiggyProps() },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Manage' }));
    await userEvent.clear(screen.getByLabelText('Application name'));
    await userEvent.type(screen.getByLabelText('Application name'), 'Updated Integration');
    await userEvent.click(screen.getByRole('button', { name: 'Save changes' }));

    // ASSERT
    expect(await screen.findByText('Choose a different application name.')).toBeVisible();
    expect(screen.getByRole('dialog')).toBeVisible();
  });

  it('given the user starts a secret regeneration, requires confirmation first', async () => {
    // ARRANGE
    render(<OAuthApplicationManagementDialogTrigger application={createOAuthClient()} />, {
      pageProps: { ziggy: createZiggyProps() },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Manage' }));
    await userEvent.click(screen.getByRole('button', { name: 'Regenerate secret' }));

    // ASSERT
    expect(screen.getByRole('alertdialog')).toBeVisible();
    expect(screen.getByText('Regenerate client secret?')).toBeVisible();
    expect(
      screen.getByText('The old secret will stop working immediately. This cannot be undone.'),
    ).toBeVisible();
  });

  it('given a secret was just regenerated, keeps the dialog open until it is acknowledged', async () => {
    // ARRANGE
    const application = createOAuthClient();
    vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: { id: application.id, secret: 'regenerated-client-secret' },
    });

    render(<OAuthApplicationManagementDialogTrigger application={application} />, {
      pageProps: { ziggy: createZiggyProps() },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Manage' }));
    await userEvent.click(screen.getByRole('button', { name: 'Regenerate secret' }));
    await userEvent.click(
      within(screen.getByRole('alertdialog')).getByRole('button', {
        name: 'Regenerate secret',
      }),
    );
    await screen.findByText("You won't be able to see the client secret again.");
    await userEvent.keyboard('{Escape}');

    // ASSERT
    expect(screen.getByRole('dialog')).toBeVisible();
    expect(screen.getByText('regene...secret')).toBeVisible();
  });

  it('given the regenerated secret was acknowledged, allows the dialog to be dismissed', async () => {
    // ARRANGE
    const application = createOAuthClient();
    vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: { id: application.id, secret: 'regenerated-client-secret' },
    });

    render(<OAuthApplicationManagementDialogTrigger application={application} />, {
      pageProps: { ziggy: createZiggyProps() },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Manage' }));
    await userEvent.click(screen.getByRole('button', { name: 'Regenerate secret' }));
    await userEvent.click(
      within(screen.getByRole('alertdialog')).getByRole('button', {
        name: 'Regenerate secret',
      }),
    );
    await screen.findByText("You won't be able to see the client secret again.");
    await userEvent.click(screen.getByRole('button', { name: "I've saved this secret" }));
    await userEvent.keyboard('{Escape}');

    // ASSERT
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
  });

  it('given a public application, does not offer secret regeneration', async () => {
    // ARRANGE
    render(
      <OAuthApplicationManagementDialogTrigger
        application={createOAuthClient({ confidential: false })}
      />,
      { pageProps: { ziggy: createZiggyProps() } },
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Manage' }));

    // ASSERT
    expect(screen.queryByRole('button', { name: 'Regenerate secret' })).not.toBeInTheDocument();
    expect(screen.queryByRole('heading', { name: 'Client secret' })).not.toBeInTheDocument();
  });
});
