import { render, screen } from '@/test';
import { createGame } from '@/test/factories';

import { CommonPlayersReason } from './CommonPlayersReason';

describe('Component: CommonPlayersReason', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <CommonPlayersReason relatedGame={createGame()} sourceGameKind="beaten" />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the source game was beaten, displays the correct label', () => {
    // ARRANGE
    render(<CommonPlayersReason relatedGame={createGame()} sourceGameKind="beaten" />);

    // ASSERT
    expect(screen.getByText(/beaten by players of/i)).toBeVisible();
  });

  it('given the source game was mastered, displays the correct label', () => {
    // ARRANGE
    render(<CommonPlayersReason relatedGame={createGame()} sourceGameKind="mastered" />);

    // ASSERT
    expect(screen.getByText(/mastered by players of/i)).toBeVisible();
  });

  it('displays a link to the game', () => {
    // ARRANGE
    const game = createGame({ title: 'Sonic the Hedgehog' });

    render(<CommonPlayersReason relatedGame={game} sourceGameKind="mastered" />);

    // ASSERT
    expect(screen.getByRole('link', { name: /sonic the hedgehog/i })).toBeVisible();
  });
});
