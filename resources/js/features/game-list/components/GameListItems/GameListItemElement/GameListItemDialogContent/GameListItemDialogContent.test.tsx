import { BaseDialog } from '@/common/components/+vendor/BaseDialog';
import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import {
  createGame,
  createGameListEntry,
  createPlayerBadge,
  createPlayerGame,
  createSystem,
} from '@/test/factories';

import { GameListItemDialogContent } from './GameListItemDialogContent';

vi.mock('@/common/components/InertiaLink', () => ({
  InertiaLink: ({ children, ...props }: any) => <a {...props}>{children}</a>,
}));

// Suppress "Warning: Missing `Description` or `aria-describedby={undefined}` for {DialogContent}."
console.warn = vi.fn();

describe('Component: GameListItemDialogContent', () => {
  let originalUrl: string;

  beforeEach(() => {
    originalUrl = window.location.href;

    Object.defineProperty(window, 'location', {
      writable: true,
      value: { href: 'http://localhost?param1=oldValue1&param2=oldValue2' },
    });
  });

  afterEach(() => {
    window.location.href = originalUrl;
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <BaseDialog open={true}>
        <GameListItemDialogContent
          gameListEntry={createGameListEntry()}
          backlogState={{
            isInBacklogMaybeOptimistic: false,
            isPending: false,
            toggleBacklog: vi.fn(),
          }}
          onToggleBacklog={vi.fn()}
        />
      </BaseDialog>,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible image representing the game', () => {
    // ARRANGE
    render(
      <BaseDialog open={true}>
        <GameListItemDialogContent
          gameListEntry={createGameListEntry({ game: createGame({ title: 'Sonic the Hedgehog' }) })}
          backlogState={{
            isInBacklogMaybeOptimistic: false,
            isPending: false,
            toggleBacklog: vi.fn(),
          }}
          onToggleBacklog={vi.fn()}
        />
      </BaseDialog>,
    );

    // ASSERT
    expect(screen.getByRole('img', { name: /sonic the hedgehog/i })).toBeVisible();
  });

  it('always shows the game title', () => {
    // ARRANGE
    render(
      <BaseDialog open={true}>
        <GameListItemDialogContent
          gameListEntry={createGameListEntry({ game: createGame({ title: 'Sonic the Hedgehog' }) })}
          backlogState={{
            isInBacklogMaybeOptimistic: false,
            isPending: false,
            toggleBacklog: vi.fn(),
          }}
          onToggleBacklog={vi.fn()}
        />
      </BaseDialog>,
    );

    // ASSERT
    expect(screen.getAllByText(/sonic the hedgehog/i)[0]).toBeVisible();
  });

  it('there is always one or more tappable links directly to the game page', () => {
    // ARRANGE
    render(
      <BaseDialog open={true}>
        <GameListItemDialogContent
          gameListEntry={createGameListEntry({ game: createGame({ title: 'Sonic the Hedgehog' }) })}
          backlogState={{
            isInBacklogMaybeOptimistic: false,
            isPending: false,
            toggleBacklog: vi.fn(),
          }}
          onToggleBacklog={vi.fn()}
        />
      </BaseDialog>,
    );

    // ASSERT
    expect(
      screen.getAllByRole('link', { name: /sonic the hedgehog/i }).length,
    ).toBeGreaterThanOrEqual(1);
  });

  it('the non-shortened game system name is always shown', () => {
    // ARRANGE
    const game = createGame({
      title: 'Sonic the Hedgehog',
      system: createSystem({ name: 'Sega Genesis/Mega Drive', nameShort: 'MD' }),
    });

    render(
      <BaseDialog open={true}>
        <GameListItemDialogContent
          gameListEntry={createGameListEntry({ game })}
          backlogState={{
            isInBacklogMaybeOptimistic: false,
            isPending: false,
            toggleBacklog: vi.fn(),
          }}
          onToggleBacklog={vi.fn()}
        />
      </BaseDialog>,
    );

    // ASSERT
    expect(screen.getByText(/sega genesis/i)).toBeVisible();
    expect(screen.getByText(/mega drive/i)).toBeVisible();
  });

  it('given there is no system to display, still renders without crashing', () => {
    // ARRANGE
    const game = createGame({
      title: 'Sonic the Hedgehog',
      system: undefined,
    });

    render(
      <BaseDialog open={true}>
        <GameListItemDialogContent
          gameListEntry={createGameListEntry({ game })}
          backlogState={{
            isInBacklogMaybeOptimistic: false,
            isPending: false,
            toggleBacklog: vi.fn(),
          }}
          onToggleBacklog={vi.fn()}
        />
      </BaseDialog>,
    );

    // ASSERT
    expect(screen.getAllByText(/sonic the hedgehog/i)[0]).toBeVisible();
  });

  it('given a game has a release date, displays it', () => {
    // ARRANGE
    const game = createGame({
      releasedAt: new Date('1987-05-05').toISOString(),
      releasedAtGranularity: 'day',
    });

    render(
      <BaseDialog open={true}>
        <GameListItemDialogContent
          gameListEntry={createGameListEntry({ game })}
          backlogState={{
            isInBacklogMaybeOptimistic: false,
            isPending: false,
            toggleBacklog: vi.fn(),
          }}
          onToggleBacklog={vi.fn()}
        />
      </BaseDialog>,
    );

    // ASSERT
    const releaseDateEl = screen.getByRole('listitem', { name: /release date/i });

    expect(releaseDateEl).toBeVisible();
    expect(releaseDateEl).toHaveTextContent(/release date/i);
    expect(releaseDateEl).toHaveTextContent('May 5, 1987');
  });

  it("given a game's release date is unknown, renders a fallback label", () => {
    // ARRANGE
    const game = createGame({
      releasedAt: null,
      releasedAtGranularity: 'day',
    });

    render(
      <BaseDialog open={true}>
        <GameListItemDialogContent
          gameListEntry={createGameListEntry({ game })}
          backlogState={{
            isInBacklogMaybeOptimistic: false,
            isPending: false,
            toggleBacklog: vi.fn(),
          }}
          onToggleBacklog={vi.fn()}
        />
      </BaseDialog>,
    );

    // ASSERT
    const releaseDateEl = screen.getByRole('listitem', { name: /release date/i });

    expect(releaseDateEl).toBeVisible();
    expect(releaseDateEl).toHaveTextContent(/release date/i);
    expect(releaseDateEl).toHaveTextContent('unknown');
  });

  it('given a game has achievements published, displays the count', () => {
    // ARRANGE
    const game = createGame({
      achievementsPublished: 123,
    });

    render(
      <BaseDialog open={true}>
        <GameListItemDialogContent
          gameListEntry={createGameListEntry({ game })}
          backlogState={{
            isInBacklogMaybeOptimistic: false,
            isPending: false,
            toggleBacklog: vi.fn(),
          }}
          onToggleBacklog={vi.fn()}
        />
      </BaseDialog>,
    );

    // ASSERT
    const achievementsPublishedEl = screen.getByRole('listitem', { name: /achievements/i });

    expect(achievementsPublishedEl).toBeVisible();
    expect(achievementsPublishedEl).toHaveTextContent(/achievements/i);
    expect(achievementsPublishedEl).toHaveTextContent('123');
  });

  it('given a game has no achievements published, displays zero', () => {
    // ARRANGE
    const game = createGame({
      achievementsPublished: undefined,
    });

    render(
      <BaseDialog open={true}>
        <GameListItemDialogContent
          gameListEntry={createGameListEntry({ game })}
          backlogState={{
            isInBacklogMaybeOptimistic: false,
            isPending: false,
            toggleBacklog: vi.fn(),
          }}
          onToggleBacklog={vi.fn()}
        />
      </BaseDialog>,
    );

    // ASSERT
    const achievementsPublishedEl = screen.getByRole('listitem', { name: /achievements/i });

    expect(achievementsPublishedEl).toBeVisible();
    expect(achievementsPublishedEl).toHaveTextContent(/achievements/i);
    expect(achievementsPublishedEl).toHaveTextContent('0');
  });

  it('given a game has points, displays the number of points', () => {
    // ARRANGE
    const game = createGame({
      pointsTotal: 100,
      pointsWeighted: 400,
    });

    render(
      <BaseDialog open={true}>
        <GameListItemDialogContent
          gameListEntry={createGameListEntry({ game })}
          backlogState={{
            isInBacklogMaybeOptimistic: false,
            isPending: false,
            toggleBacklog: vi.fn(),
          }}
          onToggleBacklog={vi.fn()}
        />
      </BaseDialog>,
    );

    // ASSERT
    const pointsTotalEl = screen.getByRole('listitem', { name: /points/i });

    expect(pointsTotalEl).toBeVisible();
    expect(pointsTotalEl).toHaveTextContent(/points/i);
    expect(pointsTotalEl).toHaveTextContent('100 (400)');
  });

  it('given a game has no weighted points, displays zero for weighted points', () => {
    // ARRANGE
    const game = createGame({
      pointsTotal: 100,
      pointsWeighted: undefined,
    });

    render(
      <BaseDialog open={true}>
        <GameListItemDialogContent
          gameListEntry={createGameListEntry({ game })}
          backlogState={{
            isInBacklogMaybeOptimistic: false,
            isPending: false,
            toggleBacklog: vi.fn(),
          }}
          onToggleBacklog={vi.fn()}
        />
      </BaseDialog>,
    );

    // ASSERT
    const pointsTotalEl = screen.getByRole('listitem', { name: /points/i });

    expect(pointsTotalEl).toBeVisible();
    expect(pointsTotalEl).toHaveTextContent(/points/i);
    expect(pointsTotalEl).toHaveTextContent('100 (0)');
  });

  it('given a game has an unknown number of points, displays zero', () => {
    // ARRANGE
    const game = createGame({
      pointsTotal: undefined,
      pointsWeighted: undefined,
    });

    render(
      <BaseDialog open={true}>
        <GameListItemDialogContent
          gameListEntry={createGameListEntry({ game })}
          backlogState={{
            isInBacklogMaybeOptimistic: false,
            isPending: false,
            toggleBacklog: vi.fn(),
          }}
          onToggleBacklog={vi.fn()}
        />
      </BaseDialog>,
    );

    // ASSERT
    const pointsTotalEl = screen.getByRole('listitem', { name: /points/i });

    expect(pointsTotalEl).toBeVisible();
    expect(pointsTotalEl).toHaveTextContent(/points/i);
    expect(pointsTotalEl).toHaveTextContent('0');
  });

  it('given a game has points, displays the RetroRatio', () => {
    // ARRANGE
    const game = createGame({
      pointsTotal: 100,
      pointsWeighted: 400,
    });

    render(
      <BaseDialog open={true}>
        <GameListItemDialogContent
          gameListEntry={createGameListEntry({ game })}
          backlogState={{
            isInBacklogMaybeOptimistic: false,
            isPending: false,
            toggleBacklog: vi.fn(),
          }}
          onToggleBacklog={vi.fn()}
        />
      </BaseDialog>,
    );

    // ASSERT
    const rarityEl = screen.getByRole('listitem', { name: /retroratio/i });

    expect(rarityEl).toBeVisible();
    expect(rarityEl).toHaveTextContent(/retroratio/i);
    expect(rarityEl).toHaveTextContent('×4.00');
  });

  it('given a game has an unknown number of points, displays a fallback label for retroratio', () => {
    // ARRANGE
    const game = createGame({
      pointsTotal: undefined,
      pointsWeighted: 400,
    });

    render(
      <BaseDialog open={true}>
        <GameListItemDialogContent
          gameListEntry={createGameListEntry({ game })}
          backlogState={{
            isInBacklogMaybeOptimistic: false,
            isPending: false,
            toggleBacklog: vi.fn(),
          }}
          onToggleBacklog={vi.fn()}
        />
      </BaseDialog>,
    );

    // ASSERT
    const rarityEl = screen.getByRole('listitem', { name: /retroratio/i });

    expect(rarityEl).toBeVisible();
    expect(rarityEl).toHaveTextContent(/retroratio/i);
    expect(rarityEl).toHaveTextContent(/none/i);
  });

  it('given a game has a player count, displays the player count', () => {
    // ARRANGE
    const game = createGame({
      playersTotal: 12345,
    });

    render(
      <BaseDialog open={true}>
        <GameListItemDialogContent
          gameListEntry={createGameListEntry({ game })}
          backlogState={{
            isInBacklogMaybeOptimistic: false,
            isPending: false,
            toggleBacklog: vi.fn(),
          }}
          onToggleBacklog={vi.fn()}
        />
      </BaseDialog>,
    );

    // ASSERT
    const playersEl = screen.getByRole('listitem', { name: /players/i });

    expect(playersEl).toBeVisible();
    expect(playersEl).toHaveTextContent(/players/i);
    expect(playersEl).toHaveTextContent('12,345');
  });

  it('given a game has an unknown number of players, falls back to zero', () => {
    // ARRANGE
    const game = createGame({
      playersTotal: undefined,
    });

    render(
      <BaseDialog open={true}>
        <GameListItemDialogContent
          gameListEntry={createGameListEntry({ game })}
          backlogState={{
            isInBacklogMaybeOptimistic: false,
            isPending: false,
            toggleBacklog: vi.fn(),
          }}
          onToggleBacklog={vi.fn()}
        />
      </BaseDialog>,
    );

    // ASSERT
    const playersEl = screen.getByRole('listitem', { name: /players/i });

    expect(playersEl).toBeVisible();
    expect(playersEl).toHaveTextContent(/players/i);
    expect(playersEl).toHaveTextContent('0');
  });

  it('given the user is unauthenticated, does not display a progress row', () => {
    // ARRANGE
    render(
      <BaseDialog open={true}>
        <GameListItemDialogContent
          gameListEntry={createGameListEntry()}
          backlogState={{
            isInBacklogMaybeOptimistic: false,
            isPending: false,
            toggleBacklog: vi.fn(),
          }}
          onToggleBacklog={vi.fn()}
        />
      </BaseDialog>,
      {
        pageProps: { auth: null },
      },
    );

    // ASSERT
    expect(screen.queryByRole('listitem', { name: /progress/i })).not.toBeInTheDocument();
  });

  it('given the user is authenticated, displays a progress row', () => {
    // ARRANGE
    const game = createGame({ achievementsPublished: 100 });
    const playerGame = createPlayerGame({
      achievementsUnlocked: 0,
      highestAward: null,
    });

    render(
      <BaseDialog open={true}>
        <GameListItemDialogContent
          gameListEntry={createGameListEntry({ game, playerGame })}
          backlogState={{
            isInBacklogMaybeOptimistic: false,
            isPending: false,
            toggleBacklog: vi.fn(),
          }}
          onToggleBacklog={vi.fn()}
        />
      </BaseDialog>,
      {
        pageProps: { auth: { user: createAuthenticatedUser() } },
      },
    );

    // ASSERT
    const progressEl = screen.getByRole('listitem', { name: /progress/i });

    expect(progressEl).toBeVisible();
    expect(progressEl).toHaveTextContent(/progress/i);
    expect(progressEl).toHaveTextContent(/none/i);
  });

  it('given the user has progress, displays a percentage of their progress in the progress row', () => {
    // ARRANGE
    const game = createGame({ achievementsPublished: 100 });
    const playerGame = createPlayerGame({
      achievementsUnlocked: 30,
      highestAward: null,
    });

    render(
      <BaseDialog open={true}>
        <GameListItemDialogContent
          gameListEntry={createGameListEntry({ game, playerGame })}
          backlogState={{
            isInBacklogMaybeOptimistic: false,
            isPending: false,
            toggleBacklog: vi.fn(),
          }}
          onToggleBacklog={vi.fn()}
        />
      </BaseDialog>,
      {
        pageProps: { auth: { user: createAuthenticatedUser() } },
      },
    );

    // ASSERT
    const progressEl = screen.getByRole('listitem', { name: /progress/i });

    expect(progressEl).toBeVisible();
    expect(progressEl).toHaveTextContent(/progress/i);
    expect(progressEl).toHaveTextContent(/30%/i);
  });

  it(
    'given the user has an award, displays an award label in the progress row',
    { retry: 2 },
    () => {
      // ARRANGE
      const game = createGame({ achievementsPublished: 100 });
      const playerGame = createPlayerGame({
        achievementsUnlocked: 30,
        highestAward: createPlayerBadge({
          awardType: 'mastery',
          awardTier: 1,
        }),
      });

      render(
        <BaseDialog open={true}>
          <GameListItemDialogContent
            gameListEntry={createGameListEntry({ game, playerGame })}
            backlogState={{
              isInBacklogMaybeOptimistic: false,
              isPending: false,
              toggleBacklog: vi.fn(),
            }}
            onToggleBacklog={vi.fn()}
          />
        </BaseDialog>,
        {
          pageProps: { auth: { user: createAuthenticatedUser() } },
        },
      );

      // ASSERT
      const progressEl = screen.getByRole('listitem', { name: /progress/i });

      expect(progressEl).toBeVisible();
      expect(progressEl).toHaveTextContent(/progress/i);
      expect(progressEl).toHaveTextContent(/30%/i);
      expect(progressEl).toHaveTextContent(/mastered/i);
    },
  );
});
