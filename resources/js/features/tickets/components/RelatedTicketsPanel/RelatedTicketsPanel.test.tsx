import { route } from 'ziggy-js';

import { render, screen } from '@/test';
import { createTicketListEntry } from '@/test/factories';

import { RelatedTicketsPanel } from './RelatedTicketsPanel';

describe('Component: RelatedTicketsPanel', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(<RelatedTicketsPanel />, {
      pageProps: {
        ticket: createTicketListEntry({ ticketableType: 'achievement' }),
        relatedTickets: [
          { id: 1, state: 'open', resolution: null, createdAt: '2024-05-01T12:00:00Z' },
        ],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given related tickets, shows the related tickets heading', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<RelatedTicketsPanel />, {
      pageProps: {
        ticket: createTicketListEntry({ ticketableType: 'achievement' }),
        relatedTickets: [
          { id: 1, state: 'open', resolution: null, createdAt: '2024-05-01T12:00:00Z' },
        ],
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { level: 2, name: 'Related Tickets' })).toBeVisible();
  });

  it('given a mix of open and closed tickets, lists open ones ahead and each state group newest first', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<RelatedTicketsPanel />, {
      pageProps: {
        ticket: createTicketListEntry({ ticketableType: 'achievement' }),
        relatedTickets: [
          { id: 1, state: 'resolved', resolution: 'fixed', createdAt: '2024-05-05T12:00:00Z' },
          { id: 2, state: 'open', resolution: null, createdAt: '2024-01-01T12:00:00Z' },
          { id: 3, state: 'request', resolution: null, createdAt: '2024-03-01T12:00:00Z' },
          { id: 4, state: 'closed', resolution: 'other', createdAt: '2024-04-01T12:00:00Z' },
        ],
      },
    });

    // ASSERT
    const orderedLinkTexts = screen.getAllByRole('link').map((linkEl) => linkEl.textContent);
    expect(orderedLinkTexts).toEqual(['#3', '#2', '#1', '#4']);
    expect(route).toHaveBeenCalledWith('ticket2.show', { ticket: 3 });
  });

  it('given a resolved ticket and an open ticket, shows the resolution/state with a relative filing date', () => {
    // ARRANGE
    vi.setSystemTime(new Date('2024-05-10T12:00:00Z'));

    render<App.Platform.Data.TicketShowPageProps>(<RelatedTicketsPanel />, {
      pageProps: {
        ticket: createTicketListEntry({ ticketableType: 'achievement' }),
        relatedTickets: [
          { id: 1, state: 'resolved', resolution: 'fixed', createdAt: '2024-05-05T12:00:00Z' },
          { id: 2, state: 'open', resolution: null, createdAt: '2024-05-08T12:00:00Z' },
        ],
      },
    });

    // ASSERT
    expect(screen.getByTitle('Fixed')).toHaveTextContent('Fixed');
    expect(screen.queryByText(/Resolved:/)).not.toBeInTheDocument();
    expect(screen.getByTitle('Open')).toHaveTextContent('Open');
    expect(screen.getByText('5d ago')).toBeVisible();
    expect(screen.getByText('2d ago')).toBeVisible();
  });

  it('given nine tickets on an achievement, caps the list at eight and links to the full achievement ticket list', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<RelatedTicketsPanel />, {
      pageProps: {
        ticket: createTicketListEntry({ ticketableType: 'achievement', ticketableId: 9001 }),
        relatedTickets: [
          { id: 1, state: 'open', resolution: null, createdAt: '2024-05-01T12:00:00Z' },
          { id: 2, state: 'open', resolution: null, createdAt: '2024-05-02T12:00:00Z' },
          { id: 3, state: 'open', resolution: null, createdAt: '2024-05-03T12:00:00Z' },
          { id: 4, state: 'open', resolution: null, createdAt: '2024-05-04T12:00:00Z' },
          { id: 5, state: 'open', resolution: null, createdAt: '2024-05-05T12:00:00Z' },
          { id: 6, state: 'open', resolution: null, createdAt: '2024-05-06T12:00:00Z' },
          { id: 7, state: 'open', resolution: null, createdAt: '2024-05-07T12:00:00Z' },
          { id: 8, state: 'open', resolution: null, createdAt: '2024-05-08T12:00:00Z' },
          { id: 9, state: 'open', resolution: null, createdAt: '2024-05-09T12:00:00Z' },
          { id: 10, state: 'open', resolution: null, createdAt: '2024-05-10T12:00:00Z' },
        ],
      },
    });

    // ASSERT
    expect(screen.getAllByRole('link', { name: /^#\d+$/ })).toHaveLength(8);
    expect(screen.queryByRole('link', { name: '#1' })).not.toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'See more' })).toHaveAttribute(
      'href',
      expect.stringContaining('achievement.tickets'),
    );
    expect(route).toHaveBeenCalledWith('achievement.tickets', {
      achievement: 9001,
      filter: { status: 'all' },
    });
  });

  it('given exactly eight tickets, lists them all without a see more link', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<RelatedTicketsPanel />, {
      pageProps: {
        ticket: createTicketListEntry({ ticketableType: 'achievement' }),
        relatedTickets: [
          { id: 1, state: 'open', resolution: null, createdAt: '2024-05-01T12:00:00Z' },
          { id: 2, state: 'open', resolution: null, createdAt: '2024-05-02T12:00:00Z' },
          { id: 3, state: 'open', resolution: null, createdAt: '2024-05-03T12:00:00Z' },
          { id: 4, state: 'open', resolution: null, createdAt: '2024-05-04T12:00:00Z' },
          { id: 5, state: 'open', resolution: null, createdAt: '2024-05-05T12:00:00Z' },
          { id: 6, state: 'open', resolution: null, createdAt: '2024-05-06T12:00:00Z' },
          { id: 7, state: 'open', resolution: null, createdAt: '2024-05-07T12:00:00Z' },
          { id: 8, state: 'open', resolution: null, createdAt: '2024-05-08T12:00:00Z' },
        ],
      },
    });

    // ASSERT
    expect(screen.getAllByRole('link', { name: /^#\d+$/ })).toHaveLength(8);
    expect(screen.queryByRole('link', { name: 'See more' })).not.toBeInTheDocument();
  });

  // FIXME in a subsequent change
  it('given nine tickets on a leaderboard, caps the list at eight without a see more link', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<RelatedTicketsPanel />, {
      pageProps: {
        ticket: createTicketListEntry({ ticketableType: 'leaderboard' }),
        relatedTickets: [
          { id: 1, state: 'open', resolution: null, createdAt: '2024-05-01T12:00:00Z' },
          { id: 2, state: 'open', resolution: null, createdAt: '2024-05-02T12:00:00Z' },
          { id: 3, state: 'open', resolution: null, createdAt: '2024-05-03T12:00:00Z' },
          { id: 4, state: 'open', resolution: null, createdAt: '2024-05-04T12:00:00Z' },
          { id: 5, state: 'open', resolution: null, createdAt: '2024-05-05T12:00:00Z' },
          { id: 6, state: 'open', resolution: null, createdAt: '2024-05-06T12:00:00Z' },
          { id: 7, state: 'open', resolution: null, createdAt: '2024-05-07T12:00:00Z' },
          { id: 8, state: 'open', resolution: null, createdAt: '2024-05-08T12:00:00Z' },
          { id: 9, state: 'open', resolution: null, createdAt: '2024-05-09T12:00:00Z' },
        ],
      },
    });

    // ASSERT
    expect(screen.getAllByRole('link', { name: /^#\d+$/ })).toHaveLength(8);
    expect(screen.queryByRole('link', { name: 'See more' })).not.toBeInTheDocument();
  });
});
