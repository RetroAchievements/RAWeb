import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createForumTopic, createForumTopicComment, createUser } from '@/test/factories';

import { ForumPostCardMeta } from './ForumPostCardMeta';

describe('Component: ForumPostCardMeta', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const topic = createForumTopic();
    const comment = createForumTopicComment();

    const { container } = render(<ForumPostCardMeta comment={comment} topic={topic} />, {
      pageProps: { can: { authorizeForumTopicComments: false } },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user is unauthorized and is the comment author, shows the unverified chip', () => {
    // ARRANGE
    const user = createAuthenticatedUser();
    const topic = createForumTopic();
    const comment = createForumTopicComment({
      isAuthorized: false,
      user: createUser({ displayName: user.displayName }),
    });

    render(<ForumPostCardMeta comment={comment} topic={topic} />, {
      pageProps: {
        auth: { user },
        can: { authorizeForumTopicComments: false },
      },
    });

    // ASSERT
    expect(screen.getByText(/unverified/i)).toBeVisible();
  });

  it('given the user can authorize comments and the comment is unauthorized, shows the unverified chip', () => {
    // ARRANGE
    const user = createAuthenticatedUser();
    const topic = createForumTopic();
    const comment = createForumTopicComment({
      isAuthorized: false,
      user: createUser({ displayName: 'Someone Else' }),
    });

    render(<ForumPostCardMeta comment={comment} topic={topic} />, {
      pageProps: {
        auth: { user },
        can: { authorizeForumTopicComments: true },
      },
    });

    // ASSERT
    expect(screen.getByText(/unverified/i)).toBeVisible();
  });

  it('given the comment is unauthorized but the user is neither the author nor can authorize comments, does not show the unverified chip', () => {
    // ARRANGE
    const user = createAuthenticatedUser();
    const topic = createForumTopic();
    const comment = createForumTopicComment({
      isAuthorized: false,
      user: createUser({ displayName: 'Someone Else' }),
    });

    render(<ForumPostCardMeta comment={comment} topic={topic} />, {
      pageProps: {
        auth: { user },
        can: { authorizeForumTopicComments: false },
      },
    });

    // ASSERT
    expect(screen.queryByText(/unverified/i)).not.toBeInTheDocument();
  });

  it('given the comment author is the original poster, shows the OP chip', () => {
    // ARRANGE
    const user = createAuthenticatedUser();
    const topic = createForumTopic({
      user: createUser({ displayName: user.displayName }),
    });
    const comment = createForumTopicComment({
      user: createUser({ displayName: user.displayName }),
    });

    render(<ForumPostCardMeta comment={comment} topic={topic} />, {
      pageProps: { can: { authorizeForumTopicComments: false } },
    });

    // ASSERT
    expect(screen.getByText(/op/i)).toBeVisible();
  });

  it('given the comment author is not the original poster, does not show the OP chip', () => {
    // ARRANGE
    const topic = createForumTopic({
      user: createUser({ displayName: 'Original Poster' }),
    });
    const comment = createForumTopicComment({
      user: createUser({ displayName: 'Someone Else' }),
    });

    render(<ForumPostCardMeta comment={comment} topic={topic} />, {
      pageProps: { can: { authorizeForumTopicComments: false } },
    });

    // ASSERT
    expect(screen.queryByText(/op/i)).not.toBeInTheDocument();
  });

  it('given the comment has a sentBy user, shows the "Posted by" text with the user avatar', () => {
    // ARRANGE
    const topic = createForumTopic();
    const teamAccount = createUser({
      displayName: 'RAdmin',
      avatarUrl: 'https://example.com/radmin-avatar.png',
    });
    const actualSender = createUser({
      displayName: 'Scott',
      avatarUrl: 'https://example.com/scott-avatar.png',
    });
    const comment = createForumTopicComment({
      user: teamAccount, // !!
      sentBy: actualSender, // !!
    });

    render(<ForumPostCardMeta comment={comment} topic={topic} />, {
      pageProps: { can: { authorizeForumTopicComments: false } },
    });

    // ASSERT
    expect(screen.getByText(/posted by/i)).toBeVisible();

    // ... check for the avatar image within the "Posted by" text ...
    const postedByAvatar = screen.getByRole('img', { name: /scott/i });
    expect(postedByAvatar).toHaveAttribute('src', 'https://example.com/scott-avatar.png');
  });

  it('given the comment does not have a sentBy user, does not show the "Sent by" text', () => {
    // ARRANGE
    const topic = createForumTopic();
    const comment = createForumTopicComment({
      user: createUser({ displayName: 'Regular User' }),
      sentBy: null, // !!
    });

    render(<ForumPostCardMeta comment={comment} topic={topic} />, {
      pageProps: { can: { authorizeForumTopicComments: false } },
    });

    // ASSERT
    expect(screen.queryByText(/sent by/i)).not.toBeInTheDocument();
  });

  it('given the comment has sentBy, shows the separator dot between timestamp and sent by', () => {
    // ARRANGE
    const topic = createForumTopic();
    const teamAccount = createUser({
      displayName: 'RAdmin',
      avatarUrl: 'https://example.com/radmin-avatar.png',
    });
    const actualSender = createUser({
      displayName: 'Scott',
      avatarUrl: 'https://example.com/scott-avatar.png',
    });
    const comment = createForumTopicComment({
      user: teamAccount,
      sentBy: actualSender, // !!
    });

    render(<ForumPostCardMeta comment={comment} topic={topic} />, {
      pageProps: { can: { authorizeForumTopicComments: false } },
    });

    // ASSERT
    const separatorDots = screen.getAllByText('·');
    expect(separatorDots.length).toBeGreaterThan(0);
  });

  it('given the comment has an editedBy user different from sentBy, shows the "Edited by" text', () => {
    // ARRANGE
    const topic = createForumTopic();
    const teamAccount = createUser({
      displayName: 'RAdmin',
      avatarUrl: 'https://example.com/radmin-avatar.png',
    });
    const originalSender = createUser({
      displayName: 'Scott',
      avatarUrl: 'https://example.com/scott-avatar.png',
    });
    const editor = createUser({
      displayName: 'Jane',
      avatarUrl: 'https://example.com/jane-avatar.png',
    });
    const comment = createForumTopicComment({
      user: teamAccount,
      sentBy: originalSender, // !!
      editedBy: editor, // !!
    });

    render(<ForumPostCardMeta comment={comment} topic={topic} />, {
      pageProps: { can: { authorizeForumTopicComments: false } },
    });

    // ASSERT
    expect(screen.getByText(/edited by/i)).toBeVisible();
    const editedByAvatar = screen.getByRole('img', { name: /jane/i });
    expect(editedByAvatar).toHaveAttribute('src', 'https://example.com/jane-avatar.png');
  });

  it('given the comment has an editedBy user who is the same as sentBy, does not show the "Edited by" text', () => {
    // ARRANGE
    const topic = createForumTopic();
    const teamAccount = createUser({
      displayName: 'RAdmin',
      avatarUrl: 'https://example.com/radmin-avatar.png',
    });
    const samePerson = createUser({
      displayName: 'Scott',
      avatarUrl: 'https://example.com/scott-avatar.png',
    });
    const comment = createForumTopicComment({
      user: teamAccount,
      sentBy: samePerson, // !!
      editedBy: samePerson, // !! same as sentBy
    });

    render(<ForumPostCardMeta comment={comment} topic={topic} />, {
      pageProps: { can: { authorizeForumTopicComments: false } },
    });

    // ASSERT
    expect(screen.getByText(/posted by/i)).toBeVisible();
    expect(screen.queryByText(/edited by/i)).not.toBeInTheDocument();
  });

  it('given the comment has editedBy but no sentBy, shows the "Edited by" text', () => {
    // ARRANGE
    const topic = createForumTopic();
    const teamAccount = createUser({
      displayName: 'RAdmin',
      avatarUrl: 'https://example.com/radmin-avatar.png',
    });
    const editor = createUser({
      displayName: 'Jane',
      avatarUrl: 'https://example.com/jane-avatar.png',
    });
    const comment = createForumTopicComment({
      user: teamAccount,
      sentBy: null, // !!
      editedBy: editor, // !!
    });

    render(<ForumPostCardMeta comment={comment} topic={topic} />, {
      pageProps: { can: { authorizeForumTopicComments: false } },
    });

    // ASSERT
    expect(screen.queryByText(/posted by/i)).not.toBeInTheDocument();
    expect(screen.getByText(/edited by/i)).toBeVisible();
  });
});
