import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { render, screen, waitFor, within } from '@/test';
import {
  createAchievement,
  createLeaderboard,
  createTicketListEntry,
  createUser,
} from '@/test/factories';

import { TicketClaimActions } from './TicketClaimActions';

describe('Component: TicketClaimActions', () => {
  it('given the viewer can neither claim nor release an existing claim on the ticket, renders no claim buttons', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketClaimActions />, {
      pageProps: {
        can: { claimTicket: false, releaseTicketClaim: false },
        ticket: createTicketListEntry(),
      },
    });

    // ASSERT
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  it('given the viewer clicks the claim button, opens a confirm dialog', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post');

    render<App.Platform.Data.TicketShowPageProps>(<TicketClaimActions />, {
      pageProps: {
        can: { claimTicket: true, releaseTicketClaim: false },
        ticket: createTicketListEntry({ id: 3917 }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Claim ticket' }));

    // ASSERT
    expect(await screen.findByRole('dialog', { name: 'Claim this ticket?' })).toBeVisible();
    expect(postSpy).not.toHaveBeenCalled();
  });

  it('given the viewer confirms the claim, claims the ticket and does a client-side reload', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { message: 'Updated' } });

    render<App.Platform.Data.TicketShowPageProps>(<TicketClaimActions />, {
      pageProps: {
        can: { claimTicket: true, releaseTicketClaim: false },
        ticket: createTicketListEntry({ id: 3917 }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Claim ticket' }));
    const dialog = await screen.findByRole('dialog', { name: 'Claim this ticket?' });
    await userEvent.click(within(dialog).getByRole('button', { name: 'Claim ticket' }));

    // ASSERT
    expect(postSpy).toHaveBeenCalledWith(route('api.ticket.claim.store', { ticket: 3917 }));
    await waitFor(() => {
      expect(router.reload).toHaveBeenCalled();
    });
  });

  it('given the claim request fails, still does a client-side reload', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockRejectedValueOnce(new Error('Forbidden'));

    render<App.Platform.Data.TicketShowPageProps>(<TicketClaimActions />, {
      pageProps: {
        can: { claimTicket: true, releaseTicketClaim: false },
        ticket: createTicketListEntry({ id: 3917 }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Claim ticket' }));
    const dialog = await screen.findByRole('dialog', { name: 'Claim this ticket?' });
    await userEvent.click(within(dialog).getByRole('button', { name: 'Claim ticket' }));

    // ASSERT
    expect(postSpy).toHaveBeenCalledWith(route('api.ticket.claim.store', { ticket: 3917 }));
    await waitFor(() => {
      expect(router.reload).toHaveBeenCalled();
    });
  });

  it('given the viewer cancels the claim dialog, makes no API call', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post');

    render<App.Platform.Data.TicketShowPageProps>(<TicketClaimActions />, {
      pageProps: {
        can: { claimTicket: true, releaseTicketClaim: false },
        ticket: createTicketListEntry({ id: 3917 }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Claim ticket' }));
    await userEvent.click(await screen.findByRole('button', { name: 'Cancel' }));

    // ASSERT
    await waitFor(() => {
      expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });
    expect(postSpy).not.toHaveBeenCalled();
  });

  it('given the viewer confirms releasing the claim, makes the API call and does a client-side reload', async () => {
    // ARRANGE
    const deleteSpy = vi
      .spyOn(axios, 'delete')
      .mockResolvedValueOnce({ data: { message: 'Updated' } });

    render<App.Platform.Data.TicketShowPageProps>(<TicketClaimActions />, {
      pageProps: {
        achievement: createAchievement(),
        can: { claimTicket: false, releaseTicketClaim: true },
        ticket: createTicketListEntry({ id: 5208 }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Release claim' }));
    const dialog = await screen.findByRole('dialog', { name: 'Release this claim?' });
    expect(deleteSpy).not.toHaveBeenCalled();
    await userEvent.click(within(dialog).getByRole('button', { name: 'Release claim' }));

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledWith(route('api.ticket.claim.destroy', { ticket: 5208 }));
    await waitFor(() => {
      expect(router.reload).toHaveBeenCalled();
    });
  });

  it('given the achievement has a maintainer, the release dialog names the maintainer', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketClaimActions />, {
      pageProps: {
        achievement: createAchievement({ developer: createUser({ displayName: 'Scott' }) }),
        maintainer: createUser({ displayName: 'Batman' }),
        can: { claimTicket: false, releaseTicketClaim: true },
        ticket: createTicketListEntry(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Release claim' }));

    // ASSERT
    const dialog = await screen.findByRole('dialog', { name: 'Release this claim?' });
    expect(dialog).toHaveTextContent('The ticket will be reassigned back to Batman.');
  });

  it('given the achievement has no maintainer, the release dialog names the achievement developer', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketClaimActions />, {
      pageProps: {
        achievement: createAchievement({ developer: createUser({ displayName: 'Scott' }) }),
        maintainer: null,
        can: { claimTicket: false, releaseTicketClaim: true },
        ticket: createTicketListEntry(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Release claim' }));

    // ASSERT
    const dialog = await screen.findByRole('dialog', { name: 'Release this claim?' });
    expect(dialog).toHaveTextContent('The ticket will be reassigned back to Scott.');
  });

  it('given a leaderboard ticket, the release dialog names the leaderboard developer', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketClaimActions />, {
      pageProps: {
        leaderboard: createLeaderboard({ developer: createUser({ displayName: 'Snow' }) }),
        can: { claimTicket: false, releaseTicketClaim: true },
        ticket: createTicketListEntry(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Release claim' }));

    // ASSERT
    const dialog = await screen.findByRole('dialog', { name: 'Release this claim?' });
    expect(dialog).toHaveTextContent('The ticket will be reassigned back to Snow.');
  });

  it('given no responsible developer is known, the release dialog has no description', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketClaimActions />, {
      pageProps: {
        can: { claimTicket: false, releaseTicketClaim: true },
        ticket: createTicketListEntry(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Release claim' }));

    // ASSERT
    const dialog = await screen.findByRole('dialog', { name: 'Release this claim?' });
    expect(dialog).not.toHaveTextContent('reassigned back to');
  });
});
