import { render, screen } from '@/test';
import { createForumTopic, createForumTopicComment, createUser } from '@/test/factories';

import { ForumCommentResultDisplay } from './ForumCommentResultDisplay';

describe('Component: ForumCommentResultDisplay', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const forumComment = createForumTopicComment();

    const { container } = render(<ForumCommentResultDisplay forumComment={forumComment} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the comment has a user, displays the user avatar and name', () => {
    // ARRANGE
    const user = createUser({ displayName: 'TestUser' });
    const forumComment = createForumTopicComment({ user });

    render(<ForumCommentResultDisplay forumComment={forumComment} />);

    // ASSERT
    expect(screen.getByRole('img', { name: /testuser/i })).toBeVisible();
    expect(screen.getByText(/testuser/i)).toBeVisible();
  });

  it('given the comment has no user (edge case), displays a fallback icon and unknown user text', () => {
    // ARRANGE
    const forumComment = createForumTopicComment({ user: undefined });

    render(<ForumCommentResultDisplay forumComment={forumComment} />);

    // ASSERT
    expect(screen.queryByRole('img')).not.toBeInTheDocument();
    expect(screen.getByText(/unknown user/i)).toBeVisible();
  });

  it('displays the comment body', () => {
    // ARRANGE
    const forumComment = createForumTopicComment({ body: 'This is my forum post content.' });

    render(<ForumCommentResultDisplay forumComment={forumComment} />);

    // ASSERT
    expect(screen.getByText(/this is my forum post content/i)).toBeVisible();
  });

  it('given the comment body is longer than 180 characters, truncates it with ellipsis', () => {
    // ARRANGE
    const longBody = 'a'.repeat(200);
    const forumComment = createForumTopicComment({ body: longBody });

    render(<ForumCommentResultDisplay forumComment={forumComment} />);

    // ASSERT
    const truncatedText = 'a'.repeat(180) + '...';
    expect(screen.getByText(truncatedText)).toBeVisible();
  });

  it('given the comment has a forum topic, displays the topic title', () => {
    // ARRANGE
    const forumTopic = createForumTopic({ title: 'Test Topic Title' });
    const forumComment = createForumTopicComment({ forumTopic });

    render(<ForumCommentResultDisplay forumComment={forumComment} />);

    // ASSERT
    expect(screen.getByText(/test topic title/i)).toBeVisible();
  });

  it('given the comment has no forum topic, does not display a topic section', () => {
    // ARRANGE
    const forumComment = createForumTopicComment({
      forumTopic: undefined,
      body: 'Some post text.',
    });

    render(<ForumCommentResultDisplay forumComment={forumComment} />);

    // ASSERT
    expect(screen.queryByText(/^in /i)).not.toBeInTheDocument();
  });

  it('displays when the comment was posted', () => {
    // ARRANGE
    const forumComment = createForumTopicComment({ body: 'Some post text.' });

    render(<ForumCommentResultDisplay forumComment={forumComment} />);

    // ASSERT
    expect(screen.getByText(/posted/i)).toBeVisible();
  });
});
