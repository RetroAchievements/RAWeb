import { route } from 'ziggy-js';

import { render, screen } from '@/test';
import { createAchievement, createGame, createTicketListEntry } from '@/test/factories';

import { TicketManualUnlockRequestNotice } from './TicketManualUnlockRequestNotice';

describe('Component: TicketManualUnlockRequestNotice', () => {
  it('given the reporter can file a manual unlock request, renders the title and a link', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketManualUnlockRequestNotice />, {
      pageProps: {
        achievement: createAchievement(),
        shouldOfferManualUnlock: true,
        ticket: createTicketListEntry(),
      },
    });

    // ASSERT
    expect(screen.getByText('You can request a manual unlock.')).toBeVisible();
    expect(screen.getByRole('link', { name: 'Start a manual unlock request' })).toBeVisible();
  });

  it('takes the game name from the ticket and points its link back at the ticket', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketManualUnlockRequestNotice />, {
      pageProps: {
        achievement: createAchievement({
          id: 3471,
          title: 'Star Chart Navigator',
          game: undefined,
        }),
        shouldOfferManualUnlock: true,
        ticket: createTicketListEntry({
          id: 2860,
          game: createGame({ title: 'Suikoden II' }),
        }),
      },
    });

    // ASSERT
    expect(route).toHaveBeenCalledWith('message-thread.create', {
      to: 'UnlockTeam',
      subject: 'Manual Unlock: Star Chart Navigator [3471] (Suikoden II)',
      message: expect.stringContaining('Ticket: [ticket=2860]'),
      templateKind: 'manual-unlock',
    });
  });

  it('given the reporter is not allowed to file a manual unlock request, renders nothing at all', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketManualUnlockRequestNotice />, {
      pageProps: {
        achievement: createAchievement(),
        shouldOfferManualUnlock: false,
        ticket: createTicketListEntry(),
      },
    });

    // ASSERT
    expect(screen.queryByText('You can request a manual unlock.')).not.toBeInTheDocument();
    expect(
      screen.queryByRole('link', { name: 'Start a manual unlock request' }),
    ).not.toBeInTheDocument();
  });
});
