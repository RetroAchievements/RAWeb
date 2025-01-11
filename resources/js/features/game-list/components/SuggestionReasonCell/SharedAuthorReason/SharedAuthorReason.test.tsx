import { render, screen } from '@/test';
import { createGame, createUser } from '@/test/factories';

import { SharedAuthorReason } from './SharedAuthorReason';

describe('Component: SharedAuthorReason', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <SharedAuthorReason relatedAuthor={createUser()} relatedGame={createGame()} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('has a link to the developer', () => {
    // ARRANGE
    const relatedAuthor = createUser({ displayName: 'Scott' });
    const relatedGame = createGame({ title: 'Dragster' });

    render(<SharedAuthorReason relatedAuthor={relatedAuthor} relatedGame={relatedGame} />);

    // ASSERT
    expect(screen.getByRole('link', { name: /same developer/i })).toBeVisible();
  });

  it('has a link to the game', () => {
    // ARRANGE
    const relatedAuthor = createUser({ displayName: 'Scott' });
    const relatedGame = createGame({ title: 'Dragster' });

    render(<SharedAuthorReason relatedAuthor={relatedAuthor} relatedGame={relatedGame} />);

    // ASSERT
    expect(screen.getByRole('link', { name: /dragster/i })).toBeVisible();
  });
});
