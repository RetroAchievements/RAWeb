import { render, screen } from '@/test';
import { createTicketListEntry } from '@/test/factories';

import { TicketShowSidebarRoot } from './TicketShowSidebarRoot';

describe('Component: TicketShowSidebarRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(<TicketShowSidebarRoot />, {
      pageProps: {
        leaderboard: null,
        ticket: createTicketListEntry({ ticketableType: 'achievement' }),
        relatedTickets: [],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given other tickets on the achievement, shows the related tickets panel', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketShowSidebarRoot />, {
      pageProps: {
        leaderboard: null,
        ticket: createTicketListEntry({ ticketableType: 'achievement' }),
        relatedTickets: [
          { id: 7, state: 'open', resolution: null, createdAt: '2024-05-01T12:00:00Z' },
        ],
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
        leaderboard: null,
        ticket: createTicketListEntry({ ticketableType: 'achievement' }),
        relatedTickets: [],
      },
    });

    // ASSERT
    expect(screen.getByRole('rowheader', { name: 'Emulator' })).toBeVisible();
    expect(screen.queryByRole('heading', { name: 'Related Tickets' })).not.toBeInTheDocument();
  });
});
