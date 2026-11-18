import { render, screen } from '@/test';
import { createTicketListEntry } from '@/test/factories';

import { TicketShowSidebarRoot } from './TicketShowSidebarRoot';

describe('Component: TicketShowSidebarRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(<TicketShowSidebarRoot />, {
      pageProps: {
        can: {},
        leaderboard: null,
        ticket: createTicketListEntry({ ticketableType: 'achievement' }),
        relatedTickets: [],
        reporterGameTickets: [],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given other tickets on the achievement, shows the related tickets panel', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketShowSidebarRoot />, {
      pageProps: {
        can: {},
        leaderboard: null,
        ticket: createTicketListEntry({ ticketableType: 'achievement' }),
        relatedTickets: [
          {
            id: 7,
            state: 'open',
            resolution: null,
            createdAt: '2024-05-01T12:00:00Z',
            ticketableTitle: 'Hookshot',
          },
        ],
        reporterGameTickets: [],
      },
    });

    // ASSERT
    expect(screen.getByRole('rowheader', { name: 'Emulator' })).toBeVisible();
    expect(screen.getByRole('heading', { level: 2, name: 'Related Tickets' })).toBeVisible();
  });

  it('given no other tickets on the achievement, does not show the related tickets panel', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketShowSidebarRoot />, {
      pageProps: {
        can: {},
        leaderboard: null,
        ticket: createTicketListEntry({ ticketableType: 'achievement' }),
        relatedTickets: [],
        reporterGameTickets: [],
      },
    });

    // ASSERT
    expect(screen.getByRole('rowheader', { name: 'Emulator' })).toBeVisible();
    expect(screen.queryByRole('heading', { name: 'Related Tickets' })).not.toBeInTheDocument();
  });

  it('given the reporter opened additional tickets on the same game, shows them in a panel', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketShowSidebarRoot />, {
      pageProps: {
        can: {},
        leaderboard: null,
        ticket: createTicketListEntry({ ticketableType: 'achievement' }),
        relatedTickets: [],
        reporterGameTickets: [
          {
            id: 21,
            state: 'open',
            resolution: null,
            createdAt: '2024-05-01T12:00:00Z',
            ticketableTitle: 'Hookshot',
          },
        ],
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { level: 2, name: 'Same Reporter, Same Game' })).toBeVisible();
  });
});
