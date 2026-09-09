import { render, screen } from '@/test';

import { TicketListEmptyState } from './TicketListEmptyState';

describe('Component: TicketListEmptyState', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<TicketListEmptyState />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('shows the empty copy', () => {
    // ARRANGE
    render(<TicketListEmptyState />);

    // ASSERT
    expect(screen.getByText('No tickets match these filters.')).toBeVisible();
  });

  it('does not claim there is no ticket history when the total is unavailable', () => {
    // ARRANGE
    render(<TicketListEmptyState unfilteredTotal={null} onViewAll={vi.fn()} />);

    // ASSERT
    expect(screen.getByText('No tickets match these filters.')).toBeVisible();
    expect(screen.queryByRole('button', { name: 'View all tickets' })).not.toBeInTheDocument();
  });

  it.each([
    ['game', 'No tickets have been reported for this game.'],
    ['achievement', 'No tickets have been reported for this achievement.'],
    ['assignedTo', 'There are no tickets in this list.'],
  ])('distinguishes a truly empty %s scope from filtered results', (scope, message) => {
    // ARRANGE
    render(<TicketListEmptyState scope={scope as any} unfilteredTotal={0} onViewAll={vi.fn()} />);

    // ASSERT
    expect(screen.getByText(message)).toBeVisible();
    expect(screen.queryByText('No tickets match these filters.')).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'View all tickets' })).not.toBeInTheDocument();
  });
});
