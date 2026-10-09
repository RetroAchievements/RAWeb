import { route } from 'ziggy-js';

import { render, screen } from '@/test';
import { createTicketListEntry } from '@/test/factories';

import { TicketRelatedFact } from './TicketRelatedFact';

describe('Component: TicketRelatedFact', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <TicketRelatedFact
        relatedTickets={[]}
        ticket={createTicketListEntry({ ticketableType: 'achievement' })}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given an achievement with no related tickets, reports that there are none', () => {
    // ARRANGE
    const { container } = render(
      <TicketRelatedFact
        relatedTickets={[]}
        ticket={createTicketListEntry({ ticketableType: 'achievement' })}
      />,
    );

    // ASSERT
    expect(container).toHaveTextContent('This achievement has no other tickets.');
  });

  it('given a leaderboard with no related tickets, reports that there are none', () => {
    // ARRANGE
    const { container } = render(
      <TicketRelatedFact
        relatedTickets={[]}
        ticket={createTicketListEntry({ ticketableType: 'leaderboard' })}
      />,
    );

    // ASSERT
    expect(container).toHaveTextContent('This leaderboard has no other tickets.');
  });

  it('given open and finished related tickets on an achievement, links each count to its ticket list', () => {
    // ARRANGE
    const { container } = render(
      <TicketRelatedFact
        relatedTickets={[
          { id: 1, state: 'open', resolution: null, createdAt: '2024-05-01T12:00:00Z' },
          { id: 2, state: 'request', resolution: null, createdAt: '2024-05-01T12:00:00Z' },
          { id: 3, state: 'resolved', resolution: 'fixed', createdAt: '2024-05-01T12:00:00Z' },
          { id: 4, state: 'closed', resolution: 'other', createdAt: '2024-05-01T12:00:00Z' },
        ]}
        ticket={createTicketListEntry({ ticketableType: 'achievement', ticketableId: 77 })}
      />,
    );

    // ASSERT
    expect(container).toHaveTextContent(
      'This achievement has 2 other open tickets. It has 2 other closed tickets.',
    );
    expect(screen.getByRole('link', { name: '2 other open tickets' })).toBeVisible();
    expect(screen.getByRole('link', { name: '2 other closed tickets' })).toBeVisible();
    expect(route).toHaveBeenCalledWith('achievement.tickets', {
      achievement: 77,
      filter: { status: 'unresolved' },
    });
    expect(route).toHaveBeenCalledWith('achievement.tickets', {
      achievement: 77,
      filter: { status: 'all' },
    });
  });

  it('given a quarantined related ticket, keeps it out of both counts', () => {
    // ARRANGE
    const { container } = render(
      <TicketRelatedFact
        relatedTickets={[
          { id: 1, state: 'open', resolution: null, createdAt: '2024-05-01T12:00:00Z' },
          { id: 2, state: 'closed', resolution: 'other', createdAt: '2024-05-01T12:00:00Z' },
          { id: 3, state: 'quarantined', resolution: null, createdAt: '2024-05-01T12:00:00Z' },
        ]}
        ticket={createTicketListEntry({ ticketableType: 'achievement' })}
      />,
    );

    // ASSERT
    expect(container).toHaveTextContent(
      'This achievement has 1 other open ticket. It has 1 other closed ticket.',
    );
  });

  it('given one open related ticket, picks the singular wording and drops the closed sentence', () => {
    // ARRANGE
    const { container } = render(
      <TicketRelatedFact
        relatedTickets={[
          { id: 1, state: 'open', resolution: null, createdAt: '2024-05-01T12:00:00Z' },
        ]}
        ticket={createTicketListEntry({ ticketableType: 'achievement' })}
      />,
    );

    // ASSERT
    expect(container).toHaveTextContent('This achievement has 1 other open ticket.');
    expect(container).not.toHaveTextContent('closed ticket');
  });

  it('given only finished related tickets, reports no other open tickets and links nothing', () => {
    // ARRANGE
    const { container } = render(
      <TicketRelatedFact
        relatedTickets={[
          { id: 1, state: 'resolved', resolution: 'fixed', createdAt: '2024-05-01T12:00:00Z' },
          { id: 2, state: 'closed', resolution: 'other', createdAt: '2024-05-01T12:00:00Z' },
        ]}
        ticket={createTicketListEntry({ ticketableType: 'achievement' })}
      />,
    );

    // ASSERT
    expect(container).toHaveTextContent(
      'This achievement has no other open tickets. It has 2 other closed tickets.',
    );
    expect(screen.queryByRole('link', { name: 'no other open tickets' })).not.toBeInTheDocument();
    expect(screen.getByRole('link', { name: '2 other closed tickets' })).toBeVisible();
  });

  it('given a leaderboard with related tickets, picks the leaderboard wording and links nothing', () => {
    // ARRANGE
    const { container } = render(
      <TicketRelatedFact
        relatedTickets={[
          { id: 1, state: 'open', resolution: null, createdAt: '2024-05-01T12:00:00Z' },
          { id: 2, state: 'closed', resolution: 'demoted', createdAt: '2024-05-01T12:00:00Z' },
        ]}
        ticket={createTicketListEntry({ ticketableType: 'leaderboard' })}
      />,
    );

    // ASSERT
    expect(container).toHaveTextContent(
      'This leaderboard has 1 other open ticket. It has 1 other closed ticket.',
    );
    expect(screen.queryByRole('link')).not.toBeInTheDocument();
  });

  it('given a leaderboard with only finished related tickets, picks the leaderboard wording', () => {
    // ARRANGE
    const { container } = render(
      <TicketRelatedFact
        relatedTickets={[
          { id: 1, state: 'closed', resolution: 'other', createdAt: '2024-05-01T12:00:00Z' },
        ]}
        ticket={createTicketListEntry({ ticketableType: 'leaderboard' })}
      />,
    );

    // ASSERT
    expect(container).toHaveTextContent('This leaderboard has no other open tickets.');
  });
});
