import { render, screen } from '@/test';
import {
  createForumTopicComment,
  createPaginatedData,
  createRecentActiveForumTopic,
  createUser,
} from '@/test/factories';

import { RecentPostsTable } from './RecentPostsTable';

describe('Component: RecentPostsTable', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<RecentPostsTable paginatedTopics={createPaginatedData([])} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders a table row for every given recent forum post', () => {
    // ARRANGE
    render(
      <RecentPostsTable
        paginatedTopics={createPaginatedData([
          createRecentActiveForumTopic(),
          createRecentActiveForumTopic(),
        ])}
      />,
    );

    // ASSERT
    expect(screen.getAllByRole('row').length).toEqual(3); // a header row and the two post rows
  });

  it('displays the topic title and the short message', () => {
    // ARRANGE
    const recentActiveForumTopic = createRecentActiveForumTopic();

    render(<RecentPostsTable paginatedTopics={createPaginatedData([recentActiveForumTopic])} />);

    // ASSERT
    expect(screen.getByText(recentActiveForumTopic.title)).toBeVisible();
    expect(screen.getByText(recentActiveForumTopic.latestComment.body)).toBeVisible();
  });

  it('displays metadata if there are multiple recent posts on the topic', () => {
    // ARRANGE
    const recentActiveForumTopic = createRecentActiveForumTopic({
      commentCount24h: 8,
      oldestComment24hId: 100,
      commentCount7d: 12,
      oldestComment7dId: 200,
    });

    render(<RecentPostsTable paginatedTopics={createPaginatedData([recentActiveForumTopic])} />);

    // ASSERT
    expect(screen.getByText(/additional posts/i)).toBeVisible();
    expect(screen.getByText(/8 posts in the last 24 hours/i)).toBeVisible();
    expect(screen.getByText(/12 posts in the last 7 days/i)).toBeVisible();
  });

  it('shows last post by metadata by default', () => {
    // ARRANGE
    const user = createUser({ displayName: 'Scott' });

    const recentActiveForumTopic = createRecentActiveForumTopic({
      latestComment: createForumTopicComment({ user }),
    });

    render(<RecentPostsTable paginatedTopics={createPaginatedData([recentActiveForumTopic])} />);

    // ASSERT
    expect(screen.queryByRole('img', { name: /scott/i })).toBeVisible();
    expect(screen.getByText(/scott/i)).toBeVisible();
  });

  it('does not render a user avatar if the last post by user is null', () => {
    // ARRANGE
    const recentActiveForumTopic = createRecentActiveForumTopic({
      latestComment: createForumTopicComment({ user: null }),
    });

    render(<RecentPostsTable paginatedTopics={createPaginatedData([recentActiveForumTopic])} />);

    // ASSERT
    expect(screen.queryByRole('img')).not.toBeInTheDocument();
  });

  it('given it is configured to not show last post by metadata, does not render it to the screen', () => {
    // ARRANGE
    const user = createUser({ displayName: 'Scott' });

    const recentActiveForumTopic = createRecentActiveForumTopic({
      latestComment: createForumTopicComment({ user }),
    });

    render(
      <RecentPostsTable
        paginatedTopics={createPaginatedData([recentActiveForumTopic])}
        showLastPostBy={false}
      />,
    );

    // ASSERT
    expect(screen.queryByRole('img')).not.toBeInTheDocument();
    expect(screen.queryByText(/scott/i)).not.toBeInTheDocument();
  });

  it('given it is configured to not show additional posts metadata, does not show additional posts', () => {
    // ARRANGE
    const recentActiveForumTopic = createRecentActiveForumTopic({
      commentCount24h: 8,
      oldestComment24hId: 100,
    });

    render(
      <RecentPostsTable
        paginatedTopics={createPaginatedData([recentActiveForumTopic])}
        showAdditionalPosts={false}
      />,
    );

    // ASSERT
    expect(screen.queryByText(/additional posts/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/posts in the last/i)).not.toBeInTheDocument();
  });
});
