import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import {
  createForumTopicComment,
  createPaginatedData,
  createRecentActiveForumTopic,
  createUser,
} from '@/test/factories';

import { RecentPostsCards } from './RecentPostsCards';

describe('Component: RecentPostsCards', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<RecentPostsCards paginatedTopics={createPaginatedData([])} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders a card for every given recent forum post', () => {
    // ARRANGE
    render(
      <RecentPostsCards
        paginatedTopics={createPaginatedData([
          createRecentActiveForumTopic(),
          createRecentActiveForumTopic(),
        ])}
      />,
    );

    // ASSERT
    expect(screen.getAllByRole('img').length).toEqual(2); // test the presence of user avatars
  });

  it('displays the topic title and the short message', () => {
    // ARRANGE
    const recentActiveForumTopic = createRecentActiveForumTopic();

    render(<RecentPostsCards paginatedTopics={createPaginatedData([recentActiveForumTopic])} />);

    // ASSERT
    expect(screen.getByText(recentActiveForumTopic.title)).toBeVisible();
    expect(screen.getByText(recentActiveForumTopic.latestComment.body)).toBeVisible();
  });

  it('shows user metadata by default', () => {
    // ARRANGE
    const user = createUser({ displayName: 'Scott' });
    const recentActiveForumTopic = createRecentActiveForumTopic({
      latestComment: createForumTopicComment({ user }),
    });

    render(<RecentPostsCards paginatedTopics={createPaginatedData([recentActiveForumTopic])} />);

    // ASSERT
    expect(screen.getByRole('img', { name: /scott/i })).toBeVisible();
  });

  it('given it is configured to not show user metadata, does not show the user', () => {
    // ARRANGE
    const user = createUser({ displayName: 'Scott' });
    const recentActiveForumTopic = createRecentActiveForumTopic({
      latestComment: createForumTopicComment({ user }),
    });

    render(
      <RecentPostsCards
        paginatedTopics={createPaginatedData([recentActiveForumTopic])}
        showUser={false}
      />,
    );

    // ASSERT
    expect(screen.queryByRole('img', { name: /scott/i })).not.toBeInTheDocument();
  });

  it('given the latest comment has a createdAt date, shows the date', () => {
    // ARRANGE
    const mockDate = new Date('2021-12-25');

    const recentActiveForumTopic = createRecentActiveForumTopic({
      latestComment: createForumTopicComment({ createdAt: mockDate.toISOString() }),
    });

    render(<RecentPostsCards paginatedTopics={createPaginatedData([recentActiveForumTopic])} />);

    // ASSERT
    expect(screen.getByTestId('timestamp')).toBeVisible();
  });

  it('given the latest comment has no createdAt date, still renders without crashing', () => {
    // ARRANGE
    const recentActiveForumTopic = createRecentActiveForumTopic({
      latestComment: createForumTopicComment({ createdAt: undefined }),
    });

    render(<RecentPostsCards paginatedTopics={createPaginatedData([recentActiveForumTopic])} />);

    // ASSERT
    expect(screen.queryByTestId('timestamp')).not.toBeInTheDocument();
  });

  it('given the user has the prefers absolute dates preference set, shows an absolute date', () => {
    // ARRANGE
    const mockDate = new Date('2021-12-25');

    const recentActiveForumTopic = createRecentActiveForumTopic({
      latestComment: createForumTopicComment({ createdAt: mockDate.toISOString() }),
    });

    render(<RecentPostsCards paginatedTopics={createPaginatedData([recentActiveForumTopic])} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            preferences: { prefersAbsoluteDates: true, shouldAlwaysBypassContentWarnings: false },
          }),
        },
      },
    });

    // ASSERT
    expect(screen.getByText('Dec 25, 2021, 00:00')).toBeVisible();
  });
});
