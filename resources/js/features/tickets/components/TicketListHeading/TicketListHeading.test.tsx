import { render, screen } from '@/test';
import { createAchievement, createGame, createSystem } from '@/test/factories';

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

  it('given no target, shows the Ticket Manager heading copy', () => {
    // ARRANGE
    render<App.Platform.Data.TicketListPageProps>(<TicketListHeading />, {
      pageProps: { scope: 'all' },
    });

    // ASSERT
    expect(screen.getByRole('heading', { level: 1, name: /ticket manager/i })).toBeVisible();
  });

  it('given a game target, shows the game breadcrumbs and heading', () => {
    // ARRANGE
    render<App.Platform.Data.TicketListPageProps>(<TicketListHeading />, {
      pageProps: {
        scope: 'game',
        game: createGame({ title: 'Sonic the Hedgehog', system: createSystem() }),
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { level: 1, name: 'Tickets' })).toBeVisible();
    expect(screen.getAllByText('Sonic the Hedgehog').length).toBeGreaterThan(0);
  });

  it('given an achievement target, shows the achievement breadcrumbs and heading', () => {
    // ARRANGE
    render<App.Platform.Data.TicketListPageProps>(<TicketListHeading />, {
      pageProps: {
        scope: 'achievement',
        achievement: createAchievement({
          title: 'Take Out the Trash',
          game: createGame({ title: 'Sonic the Hedgehog', system: createSystem() }),
        }),
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { level: 1, name: 'Tickets' })).toBeVisible();
    expect(screen.getAllByText('Take Out the Trash').length).toBeGreaterThan(0);
  });
});
