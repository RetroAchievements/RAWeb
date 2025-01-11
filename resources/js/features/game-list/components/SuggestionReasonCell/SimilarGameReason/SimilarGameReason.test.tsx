import { render, screen } from '@/test';
import { createGame } from '@/test/factories';

import { SimilarGameReason } from './SimilarGameReason';

describe('Component: SimilarGameReason', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<SimilarGameReason relatedGame={createGame()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the proper label and game', () => {
    // ARRANGE
    const game = createGame({ title: 'Sonic the Hedgehog' });

    render(<SimilarGameReason relatedGame={game} />);

    // ASSERT
    expect(screen.getByText(/similar to/i)).toBeVisible();
    expect(screen.getByRole('img', { name: /sonic the hedgehog/i })).toBeVisible();
  });
});
