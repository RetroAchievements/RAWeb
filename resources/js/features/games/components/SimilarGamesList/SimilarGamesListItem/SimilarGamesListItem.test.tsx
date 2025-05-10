import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createGame, createSystem, createZiggyProps } from '@/test/factories';

import { SimilarGamesListItem } from './SimilarGamesListItem';

describe('Component: SimilarGamesListItem', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const system = createSystem({ name: 'Test System' });
    const game = createGame({ system });
    const { container } = render(<SimilarGamesListItem game={game} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({ displayName: 'TestUser' }),
        },
        ziggy: createZiggyProps({}),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a game with achievements, displays the achievement count and points', () => {
    // ARRANGE
    const system = createSystem();
    const game = createGame({
      system,
      achievementsPublished: 15,
      pointsTotal: 7500,
    });

    render(<SimilarGamesListItem game={game} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({ displayName: 'TestUser' }),
        },
      },
    });

    // ASSERT
    expect(screen.getByText('15')).toBeVisible();
    expect(screen.getByText('7,500 points')).toBeVisible();
  });

  it('given a game with no achievements, displays the achievement count with muted styling', () => {
    // ARRANGE
    const system = createSystem();
    const game = createGame({
      system,
      achievementsPublished: 0,
      pointsTotal: 0,
    });

    render(<SimilarGamesListItem game={game} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({ displayName: 'TestUser' }),
        },
      },
    });

    // ASSERT
    const achievementElement = screen.getByText('0');
    expect(achievementElement).toBeVisible();

    const achievementContainer = achievementElement.closest('p');
    expect(achievementContainer).toHaveClass('text-neutral-600');

    const pointsElement = screen.getByText('0 points');
    expect(pointsElement).toBeVisible();
    expect(pointsElement).toHaveClass('text-neutral-600');
  });

  it('given a game with a system, displays the system chip', () => {
    // ARRANGE
    const system = createSystem({ name: 'PlayStation', nameShort: 'PS1' });
    const game = createGame({ system });

    render(<SimilarGamesListItem game={game} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({ displayName: 'TestUser' }),
        },
      },
    });

    // ASSERT
    expect(screen.getByText(/ps1/i)).toBeVisible();
  });

  it('given a game without a system, does not display a system chip', () => {
    // ARRANGE
    const game = createGame({ system: undefined });

    render(<SimilarGamesListItem game={game} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
      },
    });

    // ASSERT
    expect(screen.queryByText(/system/i)).not.toBeInTheDocument();
  });

  it('given a game, renders the game badge with correct attributes', () => {
    // ARRANGE
    const game = createGame({
      title: 'Elden Ring',
      badgeUrl: 'https://example.com/elden-ring.png',
    });

    render(<SimilarGamesListItem game={game} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
      },
    });

    // ASSERT
    const imageElement = screen.getByAltText('Elden Ring');
    expect(imageElement).toBeVisible();
    expect(imageElement).toHaveAttribute('src', 'https://example.com/elden-ring.png');
    expect(imageElement).toHaveAttribute('width', '36');
    expect(imageElement).toHaveAttribute('height', '36');
    expect(imageElement).toHaveAttribute('loading', 'lazy');
    expect(imageElement).toHaveAttribute('decoding', 'async');
  });
});
