import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import {
  createAchievement,
  createComment,
  createTicketListEntry,
  createUser,
} from '@/test/factories';

import { TicketStateMenu } from './TicketStateMenu';

describe('Component: TicketStateMenu', () => {
  it('given a developer on an open ticket, the menu shows one state move and one close item, with no resolve or demote', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketStateMenu />, {
      pageProps: {
        allowedTicketActions: [
          'resolved',
          'request',
          'demoted',
          'network',
          'wrong-rom',
          'emulator-problem',
          'already-fixed',
          'closed-mistaken',
          'closed-other',
        ],
        auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) },
        can: { createTicketComments: true },
        comments: [createComment({ user: createUser({ displayName: 'Scott' }) })],
        ticket: createTicketListEntry({
          state: 'open',
          resolution: null,
          ticketableType: 'achievement',
        }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /change status/i }));

    // ASSERT
    const itemNames = screen.getAllByRole('menuitem').map((item) => item.textContent);
    expect(itemNames).toEqual(['Request information', 'Close…']);
  });

  it('given the Close option is picked, the close dialog appears', async () => {
    // ARRANGE
    const patchSpy = vi.spyOn(axios, 'patch');

    render<App.Platform.Data.TicketShowPageProps>(<TicketStateMenu />, {
      pageProps: {
        achievement: createAchievement(),
        allowedTicketActions: ['resolved', 'network'],
        can: { createTicketComments: true },
        comments: [],
        ticket: createTicketListEntry({
          state: 'open',
          resolution: null,
          ticketableType: 'achievement',
        }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /change status/i }));
    await userEvent.click(screen.getByRole('menuitem', { name: 'Close…' }));

    // ASSERT
    expect(await screen.findByRole('dialog', { name: 'Close ticket' })).toBeVisible();
    expect(
      screen.getByRole('radiogroup', { name: 'Was something wrong with the achievement?' }),
    ).toBeVisible();
    expect(patchSpy).not.toHaveBeenCalled();
  });

  it('given the user cannot close the ticket, the menu has no Close option', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketStateMenu />, {
      pageProps: {
        allowedTicketActions: ['request'],
        auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) },
        can: { createTicketComments: true },
        comments: [createComment({ user: createUser({ displayName: 'Scott' }) })],
        ticket: createTicketListEntry({ state: 'open', resolution: null }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /change status/i }));

    // ASSERT
    const itemNames = screen.getAllByRole('menuitem').map((item) => item.textContent);
    expect(itemNames).toEqual(['Request information']);
  });

  it.each<{ expectedMenuItemLabel: string; ticketState: App.Community.Enums.TicketState }>([
    { ticketState: 'quarantined', expectedMenuItemLabel: 'Approve' },
    { ticketState: 'request', expectedMenuItemLabel: 'Stop waiting for the reporter' },
    { ticketState: 'resolved', expectedMenuItemLabel: 'Reopen' },
    { ticketState: 'closed', expectedMenuItemLabel: 'Reopen' },
  ])(
    'labels the reopen action "$expectedMenuItemLabel" on a $ticketState ticket',
    async ({ expectedMenuItemLabel, ticketState }) => {
      // ARRANGE
      const patchSpy = vi
        .spyOn(axios, 'patch')
        .mockResolvedValueOnce({ data: { message: 'Updated' } });

      render<App.Platform.Data.TicketShowPageProps>(<TicketStateMenu />, {
        pageProps: {
          allowedTicketActions: ['reopen'],
          can: {},
          comments: [],
          ticket: createTicketListEntry({ id: 5170, state: ticketState, resolution: null }),
        },
      });

      // ACT
      await userEvent.click(screen.getByRole('button', { name: /change status/i }));
      const menuItemLabels = screen
        .getAllByRole('menuitem')
        .map((menuItem) => menuItem.textContent);
      await userEvent.click(screen.getByRole('menuitem', { name: expectedMenuItemLabel }));

      // ASSERT
      expect(menuItemLabels).toEqual([expectedMenuItemLabel]);
      expect(patchSpy).toHaveBeenCalledWith(route('api.ticket.state.update', { ticket: 5170 }), {
        action: 'reopen',
      });
    },
  );

  it('given the user may only close the ticket as a mistaken report, shows only that option', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketStateMenu />, {
      pageProps: {
        allowedTicketActions: ['closed-mistaken'],
        can: {},
        comments: [],
        ticket: createTicketListEntry({ state: 'open', resolution: null }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /change status/i }));

    // ASSERT
    const itemNames = screen.getAllByRole('menuitem').map((item) => item.textContent);
    expect(itemNames).toEqual(['Mistaken report']);
  });

  it('given the newest comment was left by the current user, picking Request information then makes an immediate API call and reloads the page', async () => {
    // ARRANGE
    const patchSpy = vi
      .spyOn(axios, 'patch')
      .mockResolvedValueOnce({ data: { message: 'Updated' } });

    render<App.Platform.Data.TicketShowPageProps>(<TicketStateMenu />, {
      pageProps: {
        allowedTicketActions: ['request'],
        auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) },
        can: { createTicketComments: true },
        comments: [
          createComment({ user: createUser({ displayName: 'Reporter' }) }),
          createComment({ user: createUser({ displayName: 'Scott' }) }),
        ],
        ticket: createTicketListEntry({ id: 7731, state: 'open', resolution: null }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /change status/i }));
    await userEvent.click(screen.getByRole('menuitem', { name: 'Request information' }));

    // ASSERT
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    expect(patchSpy).toHaveBeenCalledWith(route('api.ticket.state.update', { ticket: 7731 }), {
      action: 'request',
    });
    await waitFor(() => {
      expect(router.reload).toHaveBeenCalled();
    });
  });

  it.each<{ case: string; commenterNames: string[] }>([
    { case: 'there are no comments yet', commenterNames: [] },
    { case: 'the reporter wrote the newest comment', commenterNames: ['Scott', 'Reporter'] },
  ])(
    'given $case, Request information is disabled with a hint to leave a comment first',
    async ({ commenterNames }) => {
      // ARRANGE
      const patchSpy = vi.spyOn(axios, 'patch');

      render<App.Platform.Data.TicketShowPageProps>(<TicketStateMenu />, {
        pageProps: {
          allowedTicketActions: ['request', 'network'],
          auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) },
          can: { createTicketComments: true },
          comments: commenterNames.map((displayName) =>
            createComment({ user: createUser({ displayName }) }),
          ),
          ticket: createTicketListEntry({ state: 'open', resolution: null }),
        },
      });

      // ACT
      await userEvent.click(screen.getByRole('button', { name: /change status/i }));
      const requestItem = screen.getByRole('menuitem', {
        name: /request information.*add a comment first/i,
      });
      await userEvent.click(requestItem);

      // ASSERT
      expect(requestItem).toHaveAttribute('aria-disabled', 'true');
      expect(patchSpy).not.toHaveBeenCalled();
    },
  );

  it('given the user cannot comment, excludes Request information from the menu', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketStateMenu />, {
      pageProps: {
        allowedTicketActions: ['request', 'network'],
        auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) },
        can: { createTicketComments: false },
        comments: [],
        ticket: createTicketListEntry({ state: 'open', resolution: null }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /change status/i }));

    // ASSERT
    const itemNames = screen.getAllByRole('menuitem').map((item) => item.textContent);
    expect(itemNames).toEqual(['Close…']);
  });

  it('given Mistaken report is picked, pops a confirm', async () => {
    // ARRANGE
    const patchSpy = vi
      .spyOn(axios, 'patch')
      .mockResolvedValueOnce({ data: { message: 'State updated' } });

    render<App.Platform.Data.TicketShowPageProps>(<TicketStateMenu />, {
      pageProps: {
        allowedTicketActions: ['closed-mistaken'],
        can: {},
        comments: [],
        ticket: createTicketListEntry({
          id: 6083,
          state: 'open',
          resolution: null,
          ticketableType: 'achievement',
        }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /change status/i }));
    await userEvent.click(screen.getByRole('menuitem', { name: 'Mistaken report' }));

    // ASSERT
    expect(
      await screen.findByRole('dialog', { name: 'Close as a mistaken report?' }),
    ).toBeVisible();
    expect(patchSpy).not.toHaveBeenCalled();

    await userEvent.click(screen.getByRole('button', { name: 'Close ticket' }));

    expect(patchSpy).toHaveBeenCalledWith(route('api.ticket.state.update', { ticket: 6083 }), {
      action: 'closed-mistaken',
    });
    await waitFor(() => {
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });
  });

  it('sends no API request when the confirmation is cancelled', async () => {
    // ARRANGE
    const patchSpy = vi.spyOn(axios, 'patch');

    render<App.Platform.Data.TicketShowPageProps>(<TicketStateMenu />, {
      pageProps: {
        allowedTicketActions: ['closed-mistaken'],
        can: {},
        comments: [],
        ticket: createTicketListEntry({
          state: 'open',
          resolution: null,
          ticketableType: 'achievement',
        }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /change status/i }));
    await userEvent.click(screen.getByRole('menuitem', { name: 'Mistaken report' }));
    await userEvent.click(await screen.findByRole('button', { name: 'Cancel' }));

    // ASSERT
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    expect(patchSpy).not.toHaveBeenCalled();
  });
});
