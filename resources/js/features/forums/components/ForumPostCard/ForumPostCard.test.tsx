import userEvent from '@testing-library/user-event';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import { createForumTopic, createForumTopicComment, createUser } from '@/test/factories';

import { ForumPostCard } from './ForumPostCard';

describe('Component: ForumPostCard', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ForumPostCard body="Test content" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a post body, renders it correctly', () => {
    // ARRANGE
    const body = '[b]Breaking News[/b] this is a test case';
    render(<ForumPostCard body={body} />);

    // ASSERT
    const boldEl = screen.getByText(/breaking news/i);
    expect(boldEl.nodeName).toEqual('SPAN');
    expect(boldEl).toHaveStyle('font-weight: bold');

    expect(screen.getByText(/this is a test case/i)).toBeVisible();
  });

  it('given no comment is provided, shows preview text instead of metadata', () => {
    // ARRANGE
    render(<ForumPostCard body="Test content" />, {
      pageProps: { can: { authorizeForumTopicComments: false } },
    });

    // ASSERT
    expect(screen.getByText(/preview/i)).toBeVisible();
  });

  it('given a comment and topic are provided, shows metadata instead of preview text', () => {
    // ARRANGE
    const comment = createForumTopicComment();
    const topic = createForumTopic();

    render(<ForumPostCard body="Test content" comment={comment} topic={topic} />, {
      pageProps: { can: { authorizeForumTopicComments: false } },
    });

    // ASSERT
    expect(screen.queryByText(/preview/i)).not.toBeInTheDocument();
  });

  it('given the post is highlighted, applies the highlight outline style', () => {
    // ARRANGE
    const { container } = render(<ForumPostCard body="Test content" isHighlighted={true} />, {
      pageProps: { can: { authorizeForumTopicComments: false } },
    });

    // ASSERT
    // eslint-disable-next-line testing-library/no-container -- this is a DOM node test
    expect(container.querySelector('.outline-2')).toBeInTheDocument();
  });

  it('given canManage is true and the comment is unauthorized, shows the management controls', () => {
    // ARRANGE
    const comment = createForumTopicComment({ isAuthorized: false });
    const topic = createForumTopic();

    render(<ForumPostCard body="Test content" comment={comment} topic={topic} canManage={true} />, {
      pageProps: { can: { authorizeForumTopicComments: true } },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /authorize/i })).toBeVisible();
    expect(screen.getByRole('button', { name: /block/i })).toBeVisible();
  });

  it('given canUpdate is true, shows the edit link', () => {
    // ARRANGE
    const comment = createForumTopicComment({ id: 123 });
    const topic = createForumTopic();

    render(<ForumPostCard body="Test content" comment={comment} topic={topic} canUpdate={true} />, {
      pageProps: { can: { authorizeForumTopicComments: false } },
    });

    // ASSERT
    const editLink = screen.getByRole('link', { name: 'Edit' });
    expect(editLink).toBeVisible();
    expect(editLink).toHaveAttribute('href', expect.stringContaining('forum-topic-comment.edit'));
  });

  it('given the user is authenticated and not muted, shows a report button for posts that arent theirs', () => {
    // ARRANGE
    const comment = createForumTopicComment({
      id: 456,
      user: createUser({ displayName: 'OtherUser' }),
    });
    const topic = createForumTopic();

    render(<ForumPostCard body="Test content" comment={comment} topic={topic} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({ displayName: 'CurrentUser' }),
        },
        can: { authorizeForumTopicComments: false, createModerationReports: true }, // !! can create reports
      },
    });

    // ASSERT
    const reportLink = screen.getByRole('link', { name: /report/i });
    expect(reportLink).toBeVisible();
  });

  it('given the user is viewing their own post, does not show a report button', () => {
    // ARRANGE
    const comment = createForumTopicComment({
      user: createUser({ displayName: 'CurrentUser' }),
    });
    const topic = createForumTopic();

    render(<ForumPostCard body="Test content" comment={comment} topic={topic} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({ displayName: 'CurrentUser' }),
        },
        can: { authorizeForumTopicComments: false, createModerationReports: true }, // !! can create reports
      },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /report/i })).not.toBeInTheDocument();
  });

  it('given the user lacks the createModerationReports permission, does not show a report button', () => {
    // ARRANGE
    const comment = createForumTopicComment({
      user: createUser({ displayName: 'OtherUser' }),
    });
    const topic = createForumTopic();

    render(<ForumPostCard body="Test content" comment={comment} topic={topic} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({ displayName: 'CurrentUser' }),
        },
        can: { authorizeForumTopicComments: false, createModerationReports: false }, // !!
      },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /report/i })).not.toBeInTheDocument();
  });

  it('given the user is not authenticated, does not show a report button', () => {
    // ARRANGE
    const comment = createForumTopicComment({
      user: createUser({ displayName: 'OtherUser' }),
    });
    const topic = createForumTopic();

    render(<ForumPostCard body="Test content" comment={comment} topic={topic} />, {
      pageProps: {
        auth: null, // !!
        can: { authorizeForumTopicComments: false, createModerationReports: false },
      },
    });

    // ASSERT
    expect(screen.queryByRole('link', { name: /report/i })).not.toBeInTheDocument();
  });

  it('given the post is from a blocked user and the viewer cannot manage posts, shows an inert nameless marker', () => {
    // ARRANGE
    const comment = createForumTopicComment({
      isFromBlockedUser: true,
      user: createUser({ displayName: 'BlockedUser' }),
    });
    const topic = createForumTopic();

    render(<ForumPostCard body="a masked body" comment={comment} topic={topic} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'CurrentUser' }) },
        can: {
          authorizeForumTopicComments: false,
          createModerationReports: false,
          manageForumTopicComments: false, // !!
        },
      },
    });

    // ASSERT
    expect(screen.getByText('Hidden post')).toBeVisible();
    expect(screen.queryByText(/a masked body/i)).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Show post' })).not.toBeInTheDocument();
    expect(screen.queryByText(/blockeduser/i)).not.toBeInTheDocument();
  });

  it('given the post is from a blocked user and the viewer can manage posts, hides the body behind a reveal control', () => {
    // ARRANGE
    const comment = createForumTopicComment({
      isFromBlockedUser: true,
      user: createUser({ displayName: 'BlockedUser' }),
    });
    const topic = createForumTopic();

    render(<ForumPostCard body="a masked body" comment={comment} topic={topic} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'CurrentUser' }) },
        can: {
          authorizeForumTopicComments: false,
          createModerationReports: false,
          manageForumTopicComments: true, // !!
        },
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: 'Show post' })).toBeVisible();
    expect(screen.getByText(/hidden post from blockeduser/i)).toBeVisible();
  });

  it('given the post is not from a blocked user, shows the body and no reveal control', () => {
    // ARRANGE
    const comment = createForumTopicComment({
      isFromBlockedUser: false,
      user: createUser({ displayName: 'OtherUser' }),
    });
    const topic = createForumTopic();

    render(<ForumPostCard body="a visible body" comment={comment} topic={topic} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'CurrentUser' }) },
        can: { authorizeForumTopicComments: false, createModerationReports: false },
      },
    });

    // ASSERT
    expect(screen.getByText(/a visible body/i)).toBeVisible();
    expect(screen.queryByRole('button', { name: 'Show post' })).not.toBeInTheDocument();
  });

  it('given a moderator user reveals a blocked post, shows the body and removes the reveal control', async () => {
    // ARRANGE
    const comment = createForumTopicComment({
      isFromBlockedUser: true,
      user: createUser({ displayName: 'BlockedUser' }),
    });
    const topic = createForumTopic();

    render(<ForumPostCard body="a masked body" comment={comment} topic={topic} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser({ displayName: 'CurrentUser' }) },
        can: {
          authorizeForumTopicComments: false,
          createModerationReports: false,
          manageForumTopicComments: true,
        },
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Show post' }));

    // ASSERT
    expect(screen.getByText(/a masked body/i)).toBeVisible();
    await waitFor(() => {
      expect(screen.queryByRole('button', { name: 'Show post' })).not.toBeInTheDocument();
    });
  });
});
