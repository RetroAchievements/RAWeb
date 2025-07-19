import { render, screen } from '@/test';
import { createGame, createSystem } from '@/test/factories';

import { GameResultDisplay } from './GameResultDisplay';

describe('Component: GameResultDisplay', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const game = createGame();

    const { container } = render(<GameResultDisplay game={game} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the game badge with correct attributes', () => {
    // ARRANGE
    const game = createGame({
      title: 'Super Mario Bros.',
      badgeUrl: 'https://example.com/mario-badge.png',
    });

    render(<GameResultDisplay game={game} />);

    // ACT
    const badge = screen.getByAltText(/super mario bros/i);

    // ASSERT
    expect(badge).toBeVisible();
    expect(badge).toHaveAttribute('src', 'https://example.com/mario-badge.png');
    expect(badge).toHaveAttribute('alt', 'Super Mario Bros.');
  });

  it('displays the game title', () => {
    // ARRANGE
    const game = createGame({ title: 'The Legend of Zelda' });

    render(<GameResultDisplay game={game} />);

    // ASSERT
    expect(screen.getByText(/the legend of zelda/i)).toBeVisible();
  });

  it('displays the system icon and short name', () => {
    // ARRANGE
    const system = createSystem({
      nameShort: 'NES',
      iconUrl: 'https://example.com/nes-icon.png',
    });
    const game = createGame({ system });

    render(<GameResultDisplay game={game} />);

    // ACT
    const systemIcon = screen.getAllByRole('img')[1]; // second image is the system icon

    // ASSERT
    expect(systemIcon).toBeVisible();
    expect(systemIcon).toHaveAttribute('src', 'https://example.com/nes-icon.png');
    expect(systemIcon).toHaveAttribute('width', '16');
    expect(systemIcon).toHaveAttribute('height', '16');
    expect(screen.getByText('NES')).toBeVisible();
  });

  it('displays the achievement count with formatted number', () => {
    // ARRANGE
    const game = createGame({ achievementsPublished: 1234 });

    render(<GameResultDisplay game={game} />);

    // ASSERT
    expect(screen.getByText('1,234')).toBeVisible();
  });

  it('displays the player count with formatted number', () => {
    // ARRANGE
    const game = createGame({ playersTotal: 5678 });

    render(<GameResultDisplay game={game} />);

    // ASSERT
    expect(screen.getByText('5,678')).toBeVisible();
  });

  it('given null achievement count, displays zero', () => {
    // ARRANGE
    const game = createGame({ achievementsPublished: null as any });

    render(<GameResultDisplay game={game} />);

    // ASSERT
    expect(screen.getAllByText('0')[0]).toBeVisible();
  });

  it('given null player count, displays zero', () => {
    // ARRANGE
    const game = createGame({ playersTotal: null as any });

    render(<GameResultDisplay game={game} />);

    // ASSERT
    expect(screen.getAllByText('0')[0]).toBeVisible();
  });
});
