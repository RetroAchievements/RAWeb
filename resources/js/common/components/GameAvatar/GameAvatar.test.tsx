import { render, screen } from '@/test';
import { createGame } from '@/test/factories';

import { GameAvatar } from './GameAvatar';

describe('Component: GameAvatar', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<GameAvatar {...createGame()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a game title, shows the game title on the screen', () => {
    // ARRANGE
    const game = createGame();

    render(<GameAvatar {...game} />);

    // ASSERT
    expect(screen.getByText(game.title)).toBeVisible();
  });

  it('given there is no title, still renders successfully', () => {
    // ARRANGE
    const game = createGame({ title: undefined });

    render(<GameAvatar {...game} />);

    // ASSERT
    expect(screen.getByRole('img', { name: /game/i })).toBeVisible();
  });

  it('applies the correct size to the image', () => {
    // ARRANGE
    const game = createGame();

    render(<GameAvatar {...game} size={8} />);

    // ASSERT
    const imgEl = screen.getByRole('img');

    expect(imgEl).toHaveAttribute('width', '8');
    expect(imgEl).toHaveAttribute('height', '8');
  });

  it('adds card tooltip props by default', () => {
    // ARRANGE
    const game = createGame({ id: 1 });

    render(<GameAvatar {...game} />);

    // ASSERT
    const anchorEl = screen.getByRole('link');

    expect(anchorEl).toHaveAttribute(
      'x-data',
      "tooltipComponent($el, {dynamicType: 'game', dynamicId: '1', dynamicContext: 'undefined'})",
    );
    expect(anchorEl).toHaveAttribute('x-on:mouseover', 'showTooltip($event)');
    expect(anchorEl).toHaveAttribute('x-on:mouseleave', 'hideTooltip');
    expect(anchorEl).toHaveAttribute('x-on:mousemove', 'trackMouseMovement($event)');
  });

  it('does not add card tooltip props when `hasTooltip` is false', () => {
    // ARRANGE
    const game = createGame({ id: 1 });

    render(<GameAvatar {...game} hasTooltip={false} />);

    // ASSERT
    const anchorEl = screen.getByRole('link');

    expect(anchorEl).not.toHaveAttribute('x-data');
    expect(anchorEl).not.toHaveAttribute('x-on:mouseover');
    expect(anchorEl).not.toHaveAttribute('x-on:mouseleave');
    expect(anchorEl).not.toHaveAccessibleDescription('x-on:mousemove');
  });
});
