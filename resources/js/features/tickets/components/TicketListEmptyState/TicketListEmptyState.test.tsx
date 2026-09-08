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
});
