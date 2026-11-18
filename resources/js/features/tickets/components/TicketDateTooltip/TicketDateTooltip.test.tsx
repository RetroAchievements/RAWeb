import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';

import { TicketDateTooltip } from './TicketDateTooltip';

describe('Component: TicketDateTooltip', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <TicketDateTooltip date="2024-05-03T12:00:00Z">2 days</TicketDateTooltip>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a date and children, renders the children and reveals the full date on hover', async () => {
    // ARRANGE
    render(<TicketDateTooltip date="2024-05-03T12:00:00Z">2 days</TicketDateTooltip>);

    // ACT
    await userEvent.hover(screen.getByText('2 days'));

    // ASSERT
    expect(await screen.findByRole('tooltip')).toHaveTextContent('May 3, 2024 12:00 PM');
  });
});
