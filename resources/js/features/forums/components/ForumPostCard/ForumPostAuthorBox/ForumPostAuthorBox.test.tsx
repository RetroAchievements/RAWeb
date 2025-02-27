import { render, screen } from '@/test';
import { createForumTopicComment, createUser } from '@/test/factories';

import { ForumPostAuthorBox } from './ForumPostAuthorBox';

describe('Component: ForumPostAuthorBox', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ForumPostAuthorBox />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is no comment, renders an empty container', () => {
    // ARRANGE
    render(<ForumPostAuthorBox />);

    // ASSERT
    expect(screen.getByTestId('no-author')).toBeVisible();
  });

  it('given there is a comment with an author, renders the author information', () => {
    // ARRANGE
    const user = createUser({
      id: 1,
      displayName: 'Scott',
      visibleRole: { id: 1, name: 'founder' },
    });

    const mockComment = createForumTopicComment({
      user,
    });

    render(<ForumPostAuthorBox comment={mockComment} />);

    // ASSERT
    expect(screen.getByText(user.displayName)).toBeVisible();
    expect(screen.getByText(/founder/i)).toBeVisible();
    expect(screen.getByTestId('visible-role')).toBeVisible();
    expect(screen.getByText(/joined/i)).toBeVisible();
  });

  it('given the author has no visible role, does not render a role label', () => {
    // ARRANGE
    const user = createUser({
      id: 1,
      displayName: 'Scott',
      visibleRole: null, //!!
    });

    const mockComment = createForumTopicComment({
      user,
    });

    render(<ForumPostAuthorBox comment={mockComment} />);

    // ASSERT
    expect(screen.getByText(user.displayName)).toBeVisible();
    expect(screen.queryByText(/founder/i)).not.toBeInTheDocument();
    expect(screen.queryByTestId('visible-role')).not.toBeInTheDocument();
  });

  it('given the author has been deleted, does not show their join date', () => {
    // ARRANGE
    const user = createUser({
      id: 1,
      displayName: 'Scott',
      deletedAt: new Date().toISOString(),
    });

    const mockComment = createForumTopicComment({
      user,
    });

    render(<ForumPostAuthorBox comment={mockComment} />);

    // ASSERT
    expect(screen.getByText(user.displayName)).toBeVisible();
    expect(screen.queryByText(/joined/i)).not.toBeInTheDocument();
  });
});
