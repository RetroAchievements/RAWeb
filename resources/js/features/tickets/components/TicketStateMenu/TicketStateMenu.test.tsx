import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { render, screen, waitFor } from '@/test';
import { createTicketListEntry } from '@/test/factories';

import { TicketStateMenu } from './TicketStateMenu';

describe('Component: TicketStateMenu', () => {
  it('given no allowed actions, shows the state as plain text without a menu', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketStateMenu />, {
      pageProps: {
        allowedTicketActions: [],
        hasReporterRepliedLast: false,
        ticket: createTicketListEntry({ state: 'open', resolution: null }),
      },
    });

    // ASSERT
    expect(screen.getByText('Open')).toBeVisible();
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  it('given a developer on an open ticket, lists the state moves and then the close reasons', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketStateMenu />, {
      pageProps: {
        allowedTicketActions: ['resolved', 'request', 'demoted', 'network', 'wrong-rom'],
        hasReporterRepliedLast: false,
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
    expect(itemNames).toEqual([
      'Resolve as fixed',
      'Request information',
      'Demote achievement',
      'Network problems',
      'Wrong ROM',
    ]);
    expect(screen.getByText('Close as')).toBeVisible();
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
      render<App.Platform.Data.TicketShowPageProps>(<TicketStateMenu />, {
        pageProps: {
          allowedTicketActions: ['reopen'],
          hasReporterRepliedLast: false,
          ticket: createTicketListEntry({ state: ticketState, resolution: null }),
        },
      });

      // ACT
      await userEvent.click(screen.getByRole('button', { name: /change status/i }));

      // ASSERT
      const menuItemLabels = screen
        .getAllByRole('menuitem')
        .map((menuItem) => menuItem.textContent);
      expect(menuItemLabels).toEqual([expectedMenuItemLabel]);
    },
  );

  it('given the user is the reporter, offers only closing the ticket as a mistaken report', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketStateMenu />, {
      pageProps: {
        allowedTicketActions: ['closed-mistaken'],
        hasReporterRepliedLast: false,
        ticket: createTicketListEntry({ state: 'open', resolution: null }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /change status/i }));

    // ASSERT
    const itemNames = screen.getAllByRole('menuitem').map((item) => item.textContent);
    expect(itemNames).toEqual(['Mistaken report']);
  });

  it.each<[string, App.Community.Enums.TicketAction, string]>([
    ['a state move', 'resolved', 'Resolve as fixed'],
    ['a close reason', 'wrong-rom', 'Wrong ROM'],
    ['Request information when the reporter did not reply last', 'request', 'Request information'],
  ])(
    'given the user picks %s, sends it right away and reloads the page',
    async (_description, action, itemName) => {
      // ARRANGE
      const patchSpy = vi
        .spyOn(axios, 'patch')
        .mockResolvedValueOnce({ data: { message: 'Updated' } });

      const ticket = createTicketListEntry({ id: 7731, state: 'open', resolution: null });

      render<App.Platform.Data.TicketShowPageProps>(<TicketStateMenu />, {
        pageProps: {
          allowedTicketActions: [action],
          hasReporterRepliedLast: false,
          ticket,
        },
      });

      // ACT
      await userEvent.click(screen.getByRole('button', { name: /change status/i }));
      await userEvent.click(screen.getByRole('menuitem', { name: itemName }));

      // ASSERT
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
      expect(patchSpy).toHaveBeenCalledWith(route('api.ticket.state.update', { ticket: 7731 }), {
        action,
      });
      await waitFor(() => {
        expect(router.reload).toHaveBeenCalled();
      });
    },
  );

  it.each<{
    confirmButtonName: string;
    confirmDialogTitle: string;
    menuItemName: string;
    pick: string;
    reporterRepliedLast: boolean;
    ticketAction: App.Community.Enums.TicketAction;
  }>([
    {
      pick: 'Demote',
      ticketAction: 'demoted',
      menuItemName: 'Demote achievement',
      reporterRepliedLast: false,
      confirmDialogTitle: 'Demote this achievement to unpublished?',
      confirmButtonName: 'Demote',
    },
    {
      pick: 'Unable to debug',
      ticketAction: 'unable-to-debug',
      menuItemName: 'Unable to debug (no toolkit support)',
      reporterRepliedLast: false,
      confirmDialogTitle: 'Close as unable to debug?',
      confirmButtonName: 'Close ticket',
    },
    {
      pick: 'Request information after the reporter replied last',
      ticketAction: 'request',
      menuItemName: 'Request information',
      reporterRepliedLast: true,
      confirmDialogTitle: 'Request information from the reporter?',
      confirmButtonName: 'Request information',
    },
  ])(
    'asks for confirmation before submitting the $pick pick',
    async ({
      confirmButtonName,
      confirmDialogTitle,
      menuItemName,
      reporterRepliedLast,
      ticketAction,
    }) => {
      // ARRANGE
      const patchRequestSpy = vi
        .spyOn(axios, 'patch')
        .mockResolvedValueOnce({ data: { message: 'State updated' } });

      render<App.Platform.Data.TicketShowPageProps>(<TicketStateMenu />, {
        pageProps: {
          allowedTicketActions: [ticketAction],
          hasReporterRepliedLast: reporterRepliedLast,
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
      await userEvent.click(screen.getByRole('menuitem', { name: menuItemName }));

      // ASSERT
      expect(await screen.findByRole('dialog', { name: confirmDialogTitle })).toBeVisible();
      expect(patchRequestSpy).not.toHaveBeenCalled();

      await userEvent.click(screen.getByRole('button', { name: confirmButtonName }));

      expect(patchRequestSpy).toHaveBeenCalledWith(
        route('api.ticket.state.update', { ticket: 6083 }),
        { action: ticketAction },
      );
      await waitFor(() => {
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
      });
    },
  );

  it('sends no API request when the confirmation is cancelled', async () => {
    // ARRANGE
    const patchSpy = vi.spyOn(axios, 'patch');

    render<App.Platform.Data.TicketShowPageProps>(<TicketStateMenu />, {
      pageProps: {
        allowedTicketActions: ['demoted'],
        hasReporterRepliedLast: false,
        ticket: createTicketListEntry({
          state: 'open',
          resolution: null,
          ticketableType: 'achievement',
        }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /change status/i }));
    await userEvent.click(screen.getByRole('menuitem', { name: 'Demote achievement' }));
    await userEvent.click(await screen.findByRole('button', { name: 'Cancel' }));

    // ASSERT
    expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    expect(patchSpy).not.toHaveBeenCalled();
  });

  it('given a leaderboard ticket, the demote item and confirmation specify the name of the leaderboard', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketStateMenu />, {
      pageProps: {
        allowedTicketActions: ['demoted'],
        hasReporterRepliedLast: false,
        ticket: createTicketListEntry({
          state: 'open',
          resolution: null,
          ticketableType: 'leaderboard',
        }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /change status/i }));
    await userEvent.click(screen.getByRole('menuitem', { name: 'Demote leaderboard' }));

    // ASSERT
    expect(
      await screen.findByRole('dialog', { name: 'Demote this leaderboard to unpromoted?' }),
    ).toBeVisible();
  });
});
