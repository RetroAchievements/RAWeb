import { route } from 'ziggy-js';

import { render, screen } from '@/test';
import {
  createAchievement,
  createGame,
  createLeaderboard,
  createSystem,
  createTicketListEntry,
} from '@/test/factories';

import { TicketShowBreadcrumbs } from './TicketShowBreadcrumbs';

describe('Component: TicketShowBreadcrumbs', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(<TicketShowBreadcrumbs />, {
      pageProps: {
        ticket: createTicketListEntry(),
        achievement: createAchievement(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given an achievement ticket, shows the achievement trail followed by the achievement ticket list', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketShowBreadcrumbs />, {
      pageProps: {
        ticket: createTicketListEntry({
          id: 888,
          ticketableType: 'achievement',
          ticketableId: 4402,
          ticketableTitle: 'Dragon Slayer',
          game: createGame({
            id: 812,
            title: 'Chrono Trigger',
            system: createSystem({ id: 3, name: 'SNES/Super Famicom' }),
          }),
        }),
        achievement: createAchievement({ id: 4402, title: 'Dragon Slayer' }),
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: 'More' })).toBeVisible();
    expect(screen.queryByRole('link', { name: 'All Games' })).not.toBeInTheDocument();
    expect(screen.queryByRole('link', { name: 'SNES/Super Famicom' })).not.toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'Chrono Trigger' })).toHaveAttribute(
      'href',
      expect.stringContaining('game.show'),
    );
    expect(screen.getByRole('link', { name: 'Dragon Slayer' })).toHaveAttribute(
      'href',
      expect.stringContaining('achievement.show'),
    );
    expect(screen.getByRole('link', { name: 'Tickets' })).toHaveAttribute(
      'href',
      expect.stringContaining('achievement.tickets'),
    );
    expect(screen.getByText('Ticket #888')).toBeVisible();

    expect(route).toHaveBeenCalledWith('game.show', { game: 812 });
    expect(route).toHaveBeenCalledWith('achievement.show', { achievement: 4402 });
    expect(route).toHaveBeenCalledWith('achievement.tickets', {
      achievement: 4402,
      filter: { status: 'all' },
    });
  });

  it('given a leaderboard ticket, shows the leaderboard trail with no ticket list crumb', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketShowBreadcrumbs />, {
      pageProps: {
        ticket: createTicketListEntry({
          id: 889,
          ticketableType: 'leaderboard',
          ticketableId: 77,
          ticketableTitle: 'Grand Prix Champion',
          game: createGame({ id: 813, title: 'Mario Kart 64' }),
        }),
        leaderboard: createLeaderboard({ id: 77, title: 'Grand Prix Champion' }),
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: 'More' })).toBeVisible();
    expect(screen.queryByRole('link', { name: 'All Games' })).not.toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'Mario Kart 64' })).toBeVisible();
    expect(screen.getByRole('link', { name: 'Grand Prix Champion' })).toHaveAttribute(
      'href',
      expect.stringContaining('leaderboard.show'),
    );
    expect(screen.queryByRole('link', { name: 'Tickets' })).not.toBeInTheDocument();
    expect(screen.getByText('Ticket #889')).toBeVisible();
  });
});
