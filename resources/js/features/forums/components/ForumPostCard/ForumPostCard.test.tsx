import { render, screen } from '@/test';
import { createForumTopic, createForumTopicComment } from '@/test/factories';

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
    const editLink = screen.getByRole('link', { name: /edit/i });
    expect(editLink).toBeVisible();
    expect(editLink).toHaveAttribute('href', expect.stringContaining('forum-topic-comment.edit'));
  });
});
