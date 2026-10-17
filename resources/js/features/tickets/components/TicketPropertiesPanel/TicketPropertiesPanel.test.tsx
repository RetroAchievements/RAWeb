import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import {
  createEmulator,
  createGame,
  createGameHash,
  createLeaderboard,
  createTicketListEntry,
  createUser,
} from '@/test/factories';

import { TicketPropertiesPanel } from './TicketPropertiesPanel';

describe('Component: TicketPropertiesPanel', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.TicketShowPageProps>(<TicketPropertiesPanel />, {
      pageProps: { leaderboard: null, ticket: createTicketListEntry() },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the achievement has no maintainer, identifies the author as the developer', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPropertiesPanel />, {
      pageProps: {
        leaderboard: null,
        ticket: createTicketListEntry({ author: createUser({ displayName: 'CoreDev' }) }),
        hasMaintainer: false,
      },
    });

    // ASSERT
    expect(screen.getByRole('rowheader', { name: 'Developer' })).toBeVisible();
    expect(screen.getByRole('link', { name: /CoreDev/i })).toBeVisible();
    expect(screen.queryByRole('rowheader', { name: 'Maintainer' })).not.toBeInTheDocument();
  });

  it('given the achievement has an assigned maintainer, identifies the author as the maintainer', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPropertiesPanel />, {
      pageProps: {
        leaderboard: null,
        ticket: createTicketListEntry({ author: createUser({ displayName: 'CoreDev' }) }),
        hasMaintainer: true,
      },
    });

    // ASSERT
    expect(screen.getByRole('rowheader', { name: 'Maintainer' })).toBeVisible();
    expect(screen.queryByRole('rowheader', { name: 'Developer' })).not.toBeInTheDocument();
  });

  it('given a "gone" author, displays the deleted user attribution', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPropertiesPanel />, {
      pageProps: {
        leaderboard: null,
        ticket: createTicketListEntry({ author: null }),
        hasMaintainer: false,
      },
    });

    // ASSERT
    expect(screen.getByText('Deleted user')).toBeVisible();
  });

  it('given an emulator with version information, displays the name and version together', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPropertiesPanel />, {
      pageProps: {
        leaderboard: null,
        ticket: createTicketListEntry({
          emulator: createEmulator({ name: 'RetroArch' }),
          emulatorVersion: '1.9.0',
        }),
      },
    });

    // ASSERT
    expect(screen.getByText('RetroArch 1.9.0')).toBeVisible();
  });

  it('given an emulator without version information, displays only the emulator name', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPropertiesPanel />, {
      pageProps: {
        leaderboard: null,
        ticket: createTicketListEntry({
          emulator: createEmulator({ name: 'RetroArch' }),
          emulatorVersion: null,
        }),
      },
    });

    // ASSERT
    expect(screen.getByText('RetroArch')).toBeVisible();
  });

  it('given an unknown emulator, displays the emulator as unknown', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPropertiesPanel />, {
      pageProps: {
        leaderboard: null,
        ticket: createTicketListEntry({
          emulator: null,
          emulatorVersion: null,
          gameHash: createGameHash(),
        }),
      },
    });

    // ASSERT
    expect(screen.getByText('Unknown')).toBeVisible();
  });

  it('given an emulator core, displays the core row', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPropertiesPanel />, {
      pageProps: { leaderboard: null, ticket: createTicketListEntry({ emulatorCore: 'snes9x' }) },
    });

    // ASSERT
    expect(screen.getByText('Core')).toBeVisible();
    expect(screen.getByText('snes9x')).toBeVisible();
  });

  it('given no emulator core, omits the core row', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPropertiesPanel />, {
      pageProps: { leaderboard: null, ticket: createTicketListEntry({ emulatorCore: null }) },
    });

    // ASSERT
    expect(screen.queryByText('Core')).not.toBeInTheDocument();
  });

  it('given a named game hash, links the region tag and displays the full md5 beneath', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPropertiesPanel />, {
      pageProps: {
        leaderboard: null,
        ticket: createTicketListEntry({
          game: createGame({ id: 654 }),
          gameHash: createGameHash({
            md5: '0123456789abcdef0123456789abcdef',
            name: 'Chrono Trigger (USA).sfc',
          }),
        }),
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: '(USA)' })).toHaveAttribute(
      'href',
      expect.stringContaining('game.hashes.index'),
    );
    expect(screen.getByText('0123456789abcdef0123456789abcdef')).toBeVisible();
  });

  it('given a named game hash, reveals the full filename on hover', async () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPropertiesPanel />, {
      pageProps: {
        leaderboard: null,
        ticket: createTicketListEntry({
          gameHash: createGameHash({
            md5: '0123456789abcdef0123456789abcdef',
            name: 'Chrono Trigger (USA).sfc',
          }),
        }),
      },
    });

    // ACT
    await userEvent.hover(screen.getByRole('link', { name: '(USA)' }));

    // ASSERT
    expect((await screen.findAllByText('Chrono Trigger (USA).sfc'))[0]).toBeVisible();
  });

  it('given an unnamed game hash, links the truncated md5 and displays the full md5 beneath', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPropertiesPanel />, {
      pageProps: {
        leaderboard: null,
        ticket: createTicketListEntry({
          gameHash: createGameHash({ md5: '0123456789abcdef0123456789abcdef', name: null }),
        }),
      },
    });

    // ASSERT
    expect(screen.getByRole('link', { name: '01234567' })).toBeVisible();
    expect(screen.getByText('0123456789abcdef0123456789abcdef')).toBeVisible();
  });

  it('given an unassigned game hash, displays the hash as unknown', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPropertiesPanel />, {
      pageProps: {
        leaderboard: null,
        ticket: createTicketListEntry({ emulator: createEmulator(), gameHash: null }),
      },
    });

    // ASSERT
    expect(screen.getByText('Hash')).toBeVisible();
    expect(screen.getByText('Unknown')).toBeVisible();
  });

  it('given a hardcore ticket flag, displays hardcore mode', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPropertiesPanel />, {
      pageProps: { leaderboard: null, ticket: createTicketListEntry({ hardcore: true }) },
    });

    // ASSERT
    expect(screen.getByText('Hardcore')).toBeVisible();
  });

  it('given a casual ticket, displays casual mode', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPropertiesPanel />, {
      pageProps: { leaderboard: null, ticket: createTicketListEntry({ hardcore: false }) },
    });

    // ASSERT
    expect(screen.getByText('Casual')).toBeVisible();
  });

  it('given an older ticket with no recorded mode, omits the mode row', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPropertiesPanel />, {
      pageProps: { leaderboard: null, ticket: createTicketListEntry({ hardcore: null }) },
    });

    // ASSERT
    expect(screen.queryByRole('rowheader', { name: 'Mode' })).not.toBeInTheDocument();
  });

  it('given a reported trigger version and a user with logic viewing permission, links the version tag', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPropertiesPanel />, {
      pageProps: {
        leaderboard: null,
        ticket: createTicketListEntry({ ticketableId: 88 }),
        can: { viewAchievementLogic: true },
        reportedTriggerVersion: 5,
        currentTriggerVersion: 6,
      },
    });

    // ASSERT
    expect(screen.getByText('Logic at filing')).toBeVisible();
    expect(screen.getByRole('link', { name: 'v5' })).toHaveAttribute(
      'href',
      '/manage/achievements/88/logic?version=5',
    );
  });

  it('given a reported trigger version and a user without without logic permission, displays the version tag as plain text', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPropertiesPanel />, {
      pageProps: {
        leaderboard: null,
        ticket: createTicketListEntry(),
        can: {},
        reportedTriggerVersion: 5,
        currentTriggerVersion: 6,
      },
    });

    // ASSERT
    expect(screen.getByText('v5')).toBeVisible();
    expect(screen.queryByRole('link', { name: 'v5' })).not.toBeInTheDocument();
  });

  it('given a current trigger version that differs from the reported one, displays both version rows', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPropertiesPanel />, {
      pageProps: {
        leaderboard: null,
        ticket: createTicketListEntry({ ticketableId: 88 }),
        can: { viewAchievementLogic: true },
        reportedTriggerVersion: 2,
        currentTriggerVersion: 4,
      },
    });

    // ASSERT
    expect(screen.getByRole('rowheader', { name: 'Logic at filing' })).toBeVisible();
    expect(screen.getByRole('rowheader', { name: 'Current logic' })).toBeVisible();
    expect(screen.getByRole('link', { name: 'v2' })).toHaveAttribute(
      'href',
      '/manage/achievements/88/logic?version=2',
    );
    expect(screen.getByRole('link', { name: 'v4' })).toHaveAttribute(
      'href',
      '/manage/achievements/88/logic?version=4',
    );
  });

  it('given the logic has not changed since filing, displays the same version in both rows', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPropertiesPanel />, {
      pageProps: {
        leaderboard: null,
        ticket: createTicketListEntry(),
        can: {},
        reportedTriggerVersion: 2,
        currentTriggerVersion: 2,
      },
    });

    // ASSERT
    expect(screen.getByRole('rowheader', { name: 'Current logic' })).toBeVisible();
    expect(screen.getAllByText('v2')).toHaveLength(2);
  });

  it('given an older ticket with no recorded trigger version, omits both logic version rows', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPropertiesPanel />, {
      pageProps: { leaderboard: null, ticket: createTicketListEntry() },
    });

    // ASSERT
    expect(screen.queryByText('Logic at filing')).not.toBeInTheDocument();
    expect(screen.queryByText('Current logic')).not.toBeInTheDocument();
  });

  it('given a leaderboard with a configured format, displays the format label', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPropertiesPanel />, {
      pageProps: {
        leaderboard: createLeaderboard({ format: 'SCORE' }),
        ticket: createTicketListEntry({ ticketableType: 'leaderboard' }),
      },
    });

    // ASSERT
    expect(screen.getByText('Format')).toBeVisible();
    expect(screen.getByText('Score')).toBeVisible();
  });

  it('given an ascending leaderboard, specifies lower is better', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPropertiesPanel />, {
      pageProps: {
        leaderboard: createLeaderboard({ rankAsc: true }),
        ticket: createTicketListEntry({ ticketableType: 'leaderboard' }),
      },
    });

    // ASSERT
    expect(screen.getByText('Rank order')).toBeVisible();
    expect(screen.getByText('Lower is better')).toBeVisible();
  });

  it('given a descending leaderboard, specifies higher is better', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPropertiesPanel />, {
      pageProps: {
        leaderboard: createLeaderboard({ rankAsc: false }),
        ticket: createTicketListEntry({ ticketableType: 'leaderboard' }),
      },
    });

    // ASSERT
    expect(screen.getByText('Higher is better')).toBeVisible();
  });

  it('given an achievement ticket, omits the leaderboard property rows', () => {
    // ARRANGE
    render<App.Platform.Data.TicketShowPageProps>(<TicketPropertiesPanel />, {
      pageProps: {
        leaderboard: null,
        ticket: createTicketListEntry({ ticketableType: 'achievement' }),
      },
    });

    // ASSERT
    expect(screen.queryByText('Format')).not.toBeInTheDocument();
    expect(screen.queryByText('Rank order')).not.toBeInTheDocument();
  });
});
