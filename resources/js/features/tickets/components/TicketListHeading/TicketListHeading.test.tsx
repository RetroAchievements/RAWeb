import { render, screen } from '@/test';

import { TicketListHeading } from './TicketListHeading';

describe('Component: TicketListHeading', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketListPageProps>(<TicketListHeading />, {
      pageProps: { scope: 'all' },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('shows the Ticket Manager heading copy', () => {
    // ARRANGE
    render<App.Platform.Data.TicketListPageProps>(<TicketListHeading />, {
      pageProps: { scope: 'all' },
    });

    // ASSERT
    expect(screen.getByRole('heading', { level: 1, name: /ticket manager/i })).toBeVisible();
  });
});
