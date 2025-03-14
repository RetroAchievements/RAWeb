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
});
