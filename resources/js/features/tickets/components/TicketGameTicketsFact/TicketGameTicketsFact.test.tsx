import { route } from 'ziggy-js';

import { render, screen } from '@/test';

import { TicketGameTicketsFact } from './TicketGameTicketsFact';

describe('Component: TicketGameTicketsFact', () => {
  it('given there are no other open or resolved tickets, says there are no other open tickets without a link', () => {
    // ARRANGE
    render(<TicketGameTicketsFact gameId={812} resolvedCount={0} unresolvedCount={0} />);

    // ASSERT
    expect(screen.getByText(/this game has no other open tickets/i)).toBeVisible();
    expect(screen.queryByRole('link')).not.toBeInTheDocument();
  });

  it('given there are open and resolved tickets, links each count to the filtered game ticket list', () => {
    // ARRANGE
    render(<TicketGameTicketsFact gameId={812} resolvedCount={1} unresolvedCount={37} />);

    // ASSERT
    expect(screen.getByRole('link', { name: /37 other open tickets/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /1 other resolved ticket/i })).toBeVisible();
    expect(route).toHaveBeenCalledWith('game.tickets', {
      game: 812,
      filter: { status: 'unresolved' },
    });
    expect(route).toHaveBeenCalledWith('game.tickets', {
      game: 812,
      filter: { status: 'resolved' },
    });
  });

  it('given there are only resolved tickets, says there are no other open tickets and links the resolved count', () => {
    // ARRANGE
    render(<TicketGameTicketsFact gameId={812} resolvedCount={3} unresolvedCount={0} />);

    // ASSERT
    expect(screen.getByText(/this game has no other open tickets/i)).toBeVisible();
    expect(screen.getAllByRole('link')).toHaveLength(1);
    expect(screen.getByRole('link', { name: /3 other resolved tickets/i })).toBeVisible();
  });
});
