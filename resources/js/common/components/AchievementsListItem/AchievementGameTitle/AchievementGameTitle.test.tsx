import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createGame, createSystem } from '@/test/factories';

import { AchievementGameTitle } from './AchievementGameTitle';

describe('Component: AchievementGameTitle', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<AchievementGameTitle game={createGame()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the game title and system short name', () => {
    // ARRANGE
    const system = createSystem({ nameShort: 'N64' });
    const game = createGame({ title: 'StarCraft 64', system });

    render(<AchievementGameTitle game={game} />);

    // ASSERT
    expect(screen.getByText(/starcraft/i)).toBeVisible();
    expect(screen.getByText(/n64/i)).toBeVisible();
  });

  it('given there is no system short name, does not crash and displays empty system', () => {
    // ARRANGE
    const system = createSystem({ nameShort: undefined });
    const game = createGame({ title: 'StarCraft 64', system });

    const { container } = render(<AchievementGameTitle game={game} />, {
      pageProps: { auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) } },
    });

    // ASSERT
    expect(container).toBeTruthy();
    expect(screen.getByText(/starcraft/i)).toBeVisible();
    expect(screen.getByText(/\(\)/i)).toBeVisible();
  });

  it('links to the game', () => {
    // ARRANGE
    const system = createSystem({ nameShort: 'N64' });
    const game = createGame({ title: 'StarCraft 64', system });

    render(<AchievementGameTitle game={game} />);

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /starcraft/i });

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', expect.stringContaining('game.show'));
  });

  it('adds card tooltip props', () => {
    // ARRANGE
    const system = createSystem({ nameShort: 'N64' });
    const game = createGame({ title: 'StarCraft 64', system });

    render(<AchievementGameTitle game={game} />);

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /starcraft/i });

    expect(linkEl).toHaveAttribute('x-data', expect.stringContaining('tooltipComponent'));
    expect(linkEl).toHaveAttribute(
      'x-data',
      expect.stringContaining("dynamicContext: 'undefined'"),
    );
  });

  it('given the user is authenticated, sets the card tooltip props dynamic context correctly', () => {
    // ARRANGE
    const system = createSystem({ nameShort: 'N64' });
    const game = createGame({ title: 'StarCraft 64', system });

    render(<AchievementGameTitle game={game} />, {
      pageProps: { auth: { user: createAuthenticatedUser({ displayName: 'Scott' }) } },
    });

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /starcraft/i });
    expect(linkEl).toHaveAttribute('x-data', expect.stringContaining("dynamicContext: 'Scott'"));
  });
});
