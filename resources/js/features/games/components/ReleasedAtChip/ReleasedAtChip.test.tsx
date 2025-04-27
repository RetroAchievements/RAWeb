import { formatGameReleasedAt } from '@/common/utils/formatGameReleasedAt';
import { render, screen } from '@/test';
import { createGame } from '@/test/factories';

import { ReleasedAtChip } from './ReleasedAtChip';

describe('Component: ReleasedAtChip', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const game = createGame({ releasedAt: '2024-01-01' });
    const { container } = render(<ReleasedAtChip game={game} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the game has no release date, renders nothing', () => {
    // ARRANGE
    const game = createGame({ releasedAt: null });
    render(<ReleasedAtChip game={game} />);

    // ASSERT
    expect(screen.queryByText(/released/i)).not.toBeInTheDocument();
  });

  it('given the game has a release date, formats it using the formatGameReleasedAt util', () => {
    // ARRANGE
    const game = createGame({
      releasedAt: '2024-01-01',
      releasedAtGranularity: 'day',
    });

    const expectedFormattedDate = formatGameReleasedAt(game.releasedAt, game.releasedAtGranularity);

    render(<ReleasedAtChip game={game} />);

    // ASSERT
    expect(screen.getByText(expectedFormattedDate!)).toBeVisible();
  });
});
