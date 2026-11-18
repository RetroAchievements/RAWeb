import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { render, screen, waitFor } from '@/test';
import { createTicketListEntry, createUser } from '@/test/factories';

import { TicketAwardReporterUnlockDialog } from './TicketAwardReporterUnlockDialog';

describe('Component: TicketAwardReporterUnlockDialog', () => {
  it('given a hardcore award dialog is opened, shows the reporter, achievement, and hardcore mode label', async () => {
    // ARRANGE
    render(
      <TicketAwardReporterUnlockDialog
        reporter={createUser({ displayName: 'Dana' })}
        ticket={createTicketListEntry({ ticketableTitle: 'Crate Crusher' })}
        unlockMode="hardcore"
        trigger={<BaseButton>Open</BaseButton>}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Open' }));

    // ASSERT
    expect(screen.getByRole('dialog')).toBeVisible();
    expect(screen.getByText('Award a hardcore unlock?')).toBeVisible();
    expect(screen.getByRole('dialog')).toHaveTextContent(
      'Dana will get a hardcore unlock for Crate Crusher.',
    );
  });

  it('given a casual award dialog is opened, shows the casual mode label', async () => {
    // ARRANGE
    render(
      <TicketAwardReporterUnlockDialog
        reporter={createUser({ displayName: 'Dana' })}
        ticket={createTicketListEntry({ ticketableTitle: 'Crate Crusher' })}
        unlockMode="casual"
        trigger={<BaseButton>Open</BaseButton>}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Open' }));

    // ASSERT
    expect(screen.getByText('Award a casual unlock?')).toBeVisible();
    expect(screen.getByRole('dialog')).toHaveTextContent(
      'Dana will get a casual unlock for Crate Crusher.',
    );
  });

  it('given the user cancels, sends no award request', async () => {
    // ARRANGE
    const postRequestSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ status: 204 });

    render(
      <TicketAwardReporterUnlockDialog
        reporter={createUser()}
        ticket={createTicketListEntry()}
        unlockMode="hardcore"
        trigger={<BaseButton>Open</BaseButton>}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Open' }));
    await userEvent.click(screen.getByRole('button', { name: 'Cancel' }));

    // ASSERT
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    expect(postRequestSpy.mock.calls).toEqual([]);
  });

  it('given the user confirms, sends the award in the dialog mode and reloads the unlock props', async () => {
    // ARRANGE
    const postRequestSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ status: 204 });
    const pageReloadSpy = vi.spyOn(router, 'reload').mockImplementationOnce(vi.fn());

    render(
      <TicketAwardReporterUnlockDialog
        reporter={createUser()}
        ticket={createTicketListEntry({ id: 5319 })}
        unlockMode="hardcore"
        trigger={<BaseButton>Open</BaseButton>}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Open' }));
    await userEvent.click(screen.getByRole('button', { name: 'Award hardcore' }));

    // ASSERT
    expect(postRequestSpy.mock.calls).toEqual([
      [route('api.ticket.reporter-unlock.store', { ticket: 5319 }), { isHardcore: true }],
    ]);
    await waitFor(() => {
      expect(pageReloadSpy.mock.calls).toEqual([
        [
          {
            only: [
              'hasReporterPlayedGame',
              'reporterActivity',
              'reporterUnlock',
              'unlocksSinceReported',
            ],
          },
        ],
      ]);
    });
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
  });

  it('given the award request fails, keeps the dialog open without reloading', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockRejectedValueOnce(new Error('Network error'));
    const pageReloadSpy = vi.spyOn(router, 'reload').mockImplementation(vi.fn());

    render(
      <TicketAwardReporterUnlockDialog
        reporter={createUser()}
        ticket={createTicketListEntry()}
        unlockMode="casual"
        trigger={<BaseButton>Open</BaseButton>}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Open' }));
    await userEvent.click(screen.getByRole('button', { name: 'Award casual' }));

    // ASSERT
    expect(await screen.findByText('Something went wrong.')).toBeVisible();
    expect(screen.getByRole('dialog')).toBeVisible();
    expect(pageReloadSpy.mock.calls).toEqual([]);
  });
});
