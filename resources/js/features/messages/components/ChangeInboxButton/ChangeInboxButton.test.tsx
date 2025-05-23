import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createUser } from '@/test/factories';

import { ChangeInboxButton } from './ChangeInboxButton';

describe('Component: ChangeInboxButton', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ChangeInboxButton />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) },
        selectableInboxDisplayNames: ['Scott', 'RAdmin'],
        senderUser: createUser({ displayName: 'Scott' }),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user presses the button, pops a dialog', async () => {
    // ARRANGE
    render(<ChangeInboxButton />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) },
        selectableInboxDisplayNames: ['Scott', 'RAdmin'],
        senderUser: createUser({ displayName: 'Scott' }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /change/i }));

    // ASSERT
    expect(screen.getByRole('dialog')).toBeVisible();
  });

  it('given the user selects a team inbox and submits, visits the correct URL', async () => {
    // ARRANGE
    const visitSpy = vi.spyOn(router, 'visit').mockImplementationOnce(vi.fn());

    render(<ChangeInboxButton />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) },
        selectableInboxDisplayNames: ['Scott', 'RAdmin'],
        senderUser: createUser({ displayName: 'Scott' }), // !! currently in Scott's inbox, not RAdmin's
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /change/i }));

    await userEvent.selectOptions(screen.getByLabelText(/select an account/i), ['RAdmin']);
    await userEvent.click(screen.getByRole('button', { name: /confirm/i }));

    // ASSERT
    expect(visitSpy).toHaveBeenCalledWith(['message-thread.user.index', { user: 'RAdmin' }]);
  });

  it('given the user selects their own inbox and submits, visits the correct URL', async () => {
    // ARRANGE
    const visitSpy = vi.spyOn(router, 'visit').mockImplementationOnce(vi.fn());

    render(<ChangeInboxButton />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) },
        selectableInboxDisplayNames: ['Scott', 'RAdmin'],
        senderUser: createUser({ displayName: 'RAdmin' }), // !! currently in RAdmin's inbox, not Scott's
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /change/i }));

    await userEvent.selectOptions(screen.getByLabelText(/select an account/i), ['Scott']);
    await userEvent.click(screen.getByRole('button', { name: /confirm/i }));

    // ASSERT
    expect(visitSpy).toHaveBeenCalledWith(['message-thread.index']);
  });
});
