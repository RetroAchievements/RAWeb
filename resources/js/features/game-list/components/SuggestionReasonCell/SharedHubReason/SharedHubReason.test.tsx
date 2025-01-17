import { render, screen } from '@/test';
import { createGame, createGameSet } from '@/test/factories';

import { SharedHubReason } from './SharedHubReason';

describe('Component: SharedHubReason', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <SharedHubReason relatedGame={createGame()} relatedGameSet={createGameSet()} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays a link to the hub', () => {
    // ARRANGE
    const game = createGame({ title: 'Sonic the Hedgehog' });
    const hub = createGameSet({ title: 'Platformers' });

    render(<SharedHubReason relatedGame={game} relatedGameSet={hub} />);

    // ASSERT
    expect(screen.getByRole('link', { name: /hub/i })).toBeVisible();
  });

  it('displays a link to the game', () => {
    // ARRANGE
    const game = createGame({ title: 'Sonic the Hedgehog' });
    const hub = createGameSet({ title: 'Platformers' });

    render(<SharedHubReason relatedGame={game} relatedGameSet={hub} />);

    // ASSERT
    expect(screen.getByRole('img', { name: /sonic the hedgehog/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /sonic the hedgehog/i })).toBeVisible();
  });
});
