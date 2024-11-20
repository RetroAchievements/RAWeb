import { render, screen } from '@/test';
import { createGame, createSystem } from '@/test/factories';

import { MultilineGameAvatar } from './MultilineGameAvatar';

describe('Component: MultilineGameAvatar', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<MultilineGameAvatar {...createGame()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a game title, shows the game title on the screen', () => {
    // ARRANGE
    const game = createGame();

    render(<MultilineGameAvatar {...game} />);

    // ASSERT
    expect(screen.getAllByText(game.title)[0]).toBeVisible();
  });

  it('adds card tooltip props by default', () => {
    // ARRANGE
    const game = createGame({ id: 1 });

    render(<MultilineGameAvatar {...game} />);

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

    render(<MultilineGameAvatar {...game} hasTooltip={false} />);

    // ASSERT
    const anchorEl = screen.getByRole('link');

    expect(anchorEl).not.toHaveAttribute('x-data');
    expect(anchorEl).not.toHaveAttribute('x-on:mouseover');
    expect(anchorEl).not.toHaveAttribute('x-on:mouseleave');
    expect(anchorEl).not.toHaveAccessibleDescription('x-on:mousemove');
  });

  it('displays a label for the game system', () => {
    // ARRANGE
    const system = createSystem({ id: 1, name: 'NES/Famicom' });

    const game = createGame({ system });

    render(<MultilineGameAvatar {...game} />);

    // ASSERT
    expect(screen.getByTestId('game-system')).toBeVisible();
    expect(screen.getByText('NES/Famicom')).toBeVisible();
  });

  it('still renders successfully if there is no game system provided', () => {
    // ARRANGE
    const game = createGame({ system: undefined });

    render(<MultilineGameAvatar {...game} />);

    // ASSERT
    expect(screen.queryByTestId('game-system')).not.toBeInTheDocument();
  });

  it('given a target username for hover card progress, sets tooltip context correctly', () => {
    // ARRANGE
    const game = createGame({ id: 1 });

    render(<MultilineGameAvatar {...game} showHoverCardProgressForUsername="Scott" />);

    // ASSERT
    const anchorEl = screen.getByRole('link');

    expect(anchorEl).toHaveAttribute(
      'x-data',
      "tooltipComponent($el, {dynamicType: 'game', dynamicId: '1', dynamicContext: 'Scott'})",
    );
  });

  it('given there is no explicit target username for hover card progress and the user is logged in, sets tooltip context correctly', () => {
    // ARRANGE
    const game = createGame({ id: 1 });

    render(<MultilineGameAvatar {...game} />, {
      pageProps: { auth: { user: createAuthenticatedUser({ displayName: 'Nepiki' }) } },
    });

    // ASSERT
    const anchorEl = screen.getByRole('link');

    expect(anchorEl).toHaveAttribute(
      'x-data',
      "tooltipComponent($el, {dynamicType: 'game', dynamicId: '1', dynamicContext: 'Nepiki'})",
    );
  });
});
