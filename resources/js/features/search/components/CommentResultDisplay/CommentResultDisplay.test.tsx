import { render, screen } from '@/test';
import { createComment, createUser } from '@/test/factories';

import { CommentResultDisplay } from './CommentResultDisplay';

describe('Component: CommentResultDisplay', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const comment = createComment();

    const { container } = render(<CommentResultDisplay comment={comment} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the user avatar and name', () => {
    // ARRANGE
    const user = createUser({ displayName: 'TestUser' });
    const comment = createComment({ user });

    render(<CommentResultDisplay comment={comment} />);

    // ASSERT
    expect(screen.getByRole('img', { name: /testuser/i })).toBeVisible();
    expect(screen.getByText(/testuser/i)).toBeVisible();
  });

  it('displays when the comment was posted', () => {
    // ARRANGE
    const comment = createComment();

    render(<CommentResultDisplay comment={comment} />);

    // ASSERT
    expect(screen.getByText(/posted/i)).toBeVisible();
  });

  it('displays the comment payload', () => {
    // ARRANGE
    const comment = createComment({ payload: 'This is my comment content.' });

    render(<CommentResultDisplay comment={comment} />);

    // ASSERT
    expect(screen.getByText(/this is my comment content/i)).toBeVisible();
  });

  it('given the comment payload is longer than 180 characters, truncates it with ellipsis', () => {
    // ARRANGE
    const longPayload = 'a'.repeat(200);
    const comment = createComment({ payload: longPayload });

    render(<CommentResultDisplay comment={comment} />);

    // ASSERT
    const truncatedText = 'a'.repeat(180) + '...';
    expect(screen.getByText(truncatedText)).toBeVisible();
  });

  it('given the comment is a game comment, displays the game comment label', () => {
    // ARRANGE
    const comment = createComment({ commentableType: 'game.comment' });

    render(<CommentResultDisplay comment={comment} />);

    // ASSERT
    expect(screen.getByText(/game comment/i)).toBeVisible();
  });

  it('given the comment is an achievement comment, displays the achievement comment label', () => {
    // ARRANGE
    const comment = createComment({ commentableType: 'achievement.comment' });

    render(<CommentResultDisplay comment={comment} />);

    // ASSERT
    expect(screen.getByText(/achievement comment/i)).toBeVisible();
  });

  it('given the comment is a wall comment, displays the wall comment label', () => {
    // ARRANGE
    const comment = createComment({ commentableType: 'user.comment' });

    render(<CommentResultDisplay comment={comment} />);

    // ASSERT
    expect(screen.getByText(/wall comment/i)).toBeVisible();
  });

  it('given the comment is a leaderboard comment, displays the leaderboard comment label', () => {
    // ARRANGE
    const comment = createComment({ commentableType: 'leaderboard.comment' });

    render(<CommentResultDisplay comment={comment} />);

    // ASSERT
    expect(screen.getByText(/leaderboard comment/i)).toBeVisible();
  });

  it('given the comment is a ticket comment, displays the ticket comment label', () => {
    // ARRANGE
    const comment = createComment({ commentableType: 'trigger.ticket.comment' });

    render(<CommentResultDisplay comment={comment} />);

    // ASSERT
    expect(screen.getByText(/ticket comment/i)).toBeVisible();
  });
});
