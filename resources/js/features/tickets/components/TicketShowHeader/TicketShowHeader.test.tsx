import { route } from 'ziggy-js';

import { render, screen, within } from '@/test';
import {
  createAchievement,
  createGame,
  createLeaderboard,
  createSystem,
  createTicketListEntry,
  createUser,
} from '@/test/factories';

import { TicketShowHeader } from './TicketShowHeader';

describe('Component: TicketShowHeader', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(<TicketShowHeader />, {
      pageProps: {
        achievement: createAchievement(),
        leaderboard: null,
        ticket: createTicketListEntry({ state: 'open', ticketableType: 'achievement' }),
        hasMaintainer: false,
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given an achievement, displays its badge and title linked to the achievement with points', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketShowHeader />, {
      pageProps: {
        achievement: createAchievement({ id: 4120, title: 'Crate Crusher', points: 15 }),
        leaderboard: null,
        ticket: createTicketListEntry({
          state: 'open',
          ticketableType: 'achievement',
          ticketableTitle: 'Crate Crusher',
        }),
        hasMaintainer: false,
      },
    });

    // ASSERT
    const headingEl = screen.getByRole('heading', { level: 1, name: 'Crate Crusher (15)' });

    expect(headingEl).toBeVisible();
    expect(screen.getByRole('img', { name: 'Crate Crusher' })).toBeVisible();
    expect(within(headingEl).getByRole('link', { name: 'Crate Crusher' })).toBeVisible();
    expect(route).toHaveBeenCalledWith('achievement.show', { achievement: 4120 });
  });

  it('given a leaderboard, links the set badge and title to the leaderboard view without points', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketShowHeader />, {
      pageProps: {
        achievement: null,
        leaderboard: createLeaderboard({ id: 92 }),
        ticket: createTicketListEntry({
          state: 'open',
          ticketableType: 'leaderboard',
          ticketableId: 92,
          ticketableTitle: 'Grand Prix Record',
          game: createGame({ title: 'F-Zero' }),
        }),
        hasMaintainer: false,
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { level: 1, name: 'Grand Prix Record' })).toBeVisible();
    expect(screen.getByRole('img', { name: 'F-Zero' })).toBeVisible();
    expect(screen.getByRole('link', { name: 'Grand Prix Record' })).toBeVisible();
    expect(route).toHaveBeenCalledWith('leaderboard.show', { leaderboard: 92 });
  });

  it('displays the game title link and system short name', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketShowHeader />, {
      pageProps: {
        achievement: createAchievement(),
        leaderboard: null,
        ticket: createTicketListEntry({
          state: 'open',
          ticketableType: 'achievement',
          game: createGame({
            id: 654,
            title: 'Chrono Trigger',
            system: createSystem({ nameShort: 'SNES' }),
          }),
        }),
        hasMaintainer: false,
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: 'Chrono Trigger' })).toBeVisible();
    expect(screen.getByText('SNES')).toBeVisible();
    expect(route).toHaveBeenCalledWith('game.show', { game: 654 });
  });

  it('given a resolved ticket with an active resolution, displays the combined state and resolution label', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketShowHeader />, {
      pageProps: {
        achievement: createAchievement(),
        leaderboard: null,
        ticket: createTicketListEntry({
          state: 'resolved',
          resolution: 'fixed',
          ticketableType: 'achievement',
        }),
        hasMaintainer: false,
      },
    });

    // ASSERT
    expect(screen.getByText('Resolved: Fixed')).toBeVisible();
  });

  it.each<[App.Community.Enums.TicketState, App.Community.Enums.TicketResolution | null, string]>([
    ['closed', 'mistaken_report', 'Closed: Mistaken report'],
    ['resolved', null, 'Resolved'],
    ['open', null, 'Open'],
  ])(
    'given a %s ticket with resolution %s, displays the state label "%s"',
    (state, resolution, expectedLabel) => {
      // ARRANGE
      render<App.Platform.Data.TicketShowPageProps>(<TicketShowHeader />, {
        pageProps: {
          achievement: createAchievement(),
          leaderboard: null,
          ticket: createTicketListEntry({ state, resolution, ticketableType: 'achievement' }),
          hasMaintainer: false,
        },
      });

      // ASSERT
      expect(screen.getByText(expectedLabel)).toBeVisible();
    },
  );

  it('displays the ticket reporter attribution', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(<TicketShowHeader />, {
      pageProps: {
        achievement: createAchievement(),
        leaderboard: null,
        ticket: createTicketListEntry({
          state: 'open',
          ticketableType: 'achievement',
          reporter: createUser({ displayName: 'PixelHero' }),
        }),
        hasMaintainer: false,
      },
    });

    // ASSERT
    expect(container).toHaveTextContent(/Reported by PixelHero/);
  });

  it('given a missing reporter reference, displays the deleted user label', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(<TicketShowHeader />, {
      pageProps: {
        achievement: createAchievement(),
        leaderboard: null,
        ticket: createTicketListEntry({
          state: 'open',
          ticketableType: 'achievement',
          reporter: null,
        }),
        hasMaintainer: false,
      },
    });

    // ASSERT
    expect(container).toHaveTextContent(/Reported by Deleted user/);
  });
});
