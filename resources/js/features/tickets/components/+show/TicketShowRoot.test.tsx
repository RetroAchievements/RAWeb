import { render, screen } from '@/test';
import {
  createAchievement,
  createGame,
  createLeaderboard,
  createTicketListEntry,
  createUser,
} from '@/test/factories';

import { TicketShowRoot } from './TicketShowRoot';

describe('Component: TicketShowRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(<TicketShowRoot />, {
      pageProps: {
        can: {},
        relatedTickets: [],
        achievement: createAchievement(),
        ticket: createTicketListEntry({ state: 'open', ticketableType: 'achievement' }),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given an achievement ticket, displays breadcrumbs, the achievement header, and the facts list', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketShowRoot />, {
      pageProps: {
        can: {},
        relatedTickets: [],
        achievement: createAchievement({ title: 'Crate Crusher', points: 15 }),
        ticket: createTicketListEntry({
          id: 888,
          state: 'open',
          ticketableType: 'achievement',
          ticketableTitle: 'Crate Crusher',
          game: createGame({ title: 'Crash Bandicoot' }),
          reporter: createUser({ displayName: 'PixelHero' }),
        }),
      },
    });

    // ASSERT
    expect(screen.getByText('Ticket #888')).toBeVisible();
    expect(screen.getByRole('heading', { level: 1, name: 'Crate Crusher (15)' })).toBeVisible();
    expect(screen.getByText('This achievement has no other tickets.')).toBeVisible();
  });

  it('given a leaderboard ticket, displays the leaderboard header', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketShowRoot />, {
      pageProps: {
        can: {},
        relatedTickets: [],
        leaderboard: createLeaderboard({ format: 'SCORE', rankAsc: true }),
        ticket: createTicketListEntry({
          state: 'open',
          ticketableType: 'leaderboard',
          ticketableTitle: 'Grand Prix Record',
        }),
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { level: 1, name: 'Grand Prix Record' })).toBeVisible();
  });
});
