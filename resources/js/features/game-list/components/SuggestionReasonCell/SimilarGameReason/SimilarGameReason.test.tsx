import { render, screen } from '@/test';
import { createGame } from '@/test/factories';

import { SimilarGameReason } from './SimilarGameReason';

describe('Component: SimilarGameReason', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <SimilarGameReason relatedGame={createGame()} sourceGameKind="beaten" />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given similar to a beaten game, displays the proper label and game', () => {
    // ARRANGE
    const game = createGame({ title: 'Sonic the Hedgehog' });

    render(<SimilarGameReason relatedGame={game} sourceGameKind="beaten" />);

    // ASSERT
    expect(screen.getByText(/similar to beaten/i)).toBeVisible();
    expect(screen.getByRole('img', { name: /sonic the hedgehog/i })).toBeVisible();
  });

  it('given similar to a mastered game, displays the proper label and game', () => {
    // ARRANGE
    const game = createGame({ title: 'Sonic the Hedgehog' });

    render(<SimilarGameReason relatedGame={game} sourceGameKind="mastered" />);

    // ASSERT
    expect(screen.getByText(/similar to mastered/i)).toBeVisible();
    expect(screen.getByRole('img', { name: /sonic the hedgehog/i })).toBeVisible();
  });

  it('given similar to a backlog game, displays the proper label and game', () => {
    // ARRANGE
    const game = createGame({ title: 'Sonic the Hedgehog' });

    render(<SimilarGameReason relatedGame={game} sourceGameKind="want-to-play" />);

    // ASSERT
    expect(screen.getByText(/similar to backlog/i)).toBeVisible();
    expect(screen.getByRole('img', { name: /sonic the hedgehog/i })).toBeVisible();
  });

  it('given there is no source game kind, still shows a label', () => {
    // ARRANGE
    const game = createGame({ title: 'Sonic the Hedgehog' });

    render(<SimilarGameReason relatedGame={game} sourceGameKind={null} />);

    // ASSERT
    expect(screen.getByText('Similar to')).toBeVisible();
    expect(screen.getByRole('img', { name: /sonic the hedgehog/i })).toBeVisible();
  });

  it('given the related game is null, displays the correct label', () => {
    // ARRANGE
    render(<SimilarGameReason relatedGame={null} sourceGameKind={null} />);

    // ASSERT
    expect(screen.getByText('Similar')).toBeVisible();
  });
});
