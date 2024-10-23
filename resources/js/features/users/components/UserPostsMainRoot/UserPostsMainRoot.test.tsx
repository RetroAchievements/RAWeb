import { render, screen } from '@/test';
import { createPaginatedData, createRecentActiveForumTopic, createUser } from '@/test/factories';

import { UserPostsMainRoot } from './UserPostsMainRoot';

describe('Component: UserPostsMainRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Community.Data.UserRecentPostsPageProps>(
      <UserPostsMainRoot />,
      {
        pageProps: { targetUser: createUser(), paginatedTopics: createPaginatedData([]) },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays breadcrumbs', () => {
    // ARRANGE
    const user = createUser({ displayName: 'Scott' });

    render<App.Community.Data.UserRecentPostsPageProps>(<UserPostsMainRoot />, {
      pageProps: { targetUser: user, paginatedTopics: createPaginatedData([]) },
    });

    // ASSERT
    expect(screen.getByRole('navigation', { name: /breadcrumb/i })).toBeVisible();
    expect(screen.getAllByRole('link', { name: /scott/i })[0]).toBeVisible();
  });

  it('displays an accessible heading', () => {
    // ARRANGE
    const user = createUser({ displayName: 'Scott' });

    render<App.Community.Data.UserRecentPostsPageProps>(<UserPostsMainRoot />, {
      pageProps: { targetUser: user, paginatedTopics: createPaginatedData([]) },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /scott's forum posts/i })).toBeVisible();
  });

  it('given there are no posts, displays an empty state', () => {
    // ARRANGE
    render<App.Community.Data.UserRecentPostsPageProps>(<UserPostsMainRoot />, {
      pageProps: { targetUser: createUser(), paginatedTopics: createPaginatedData([]) },
    });

    // ASSERT
    expect(screen.getByText(/doesn't have any forum posts/i)).toBeVisible();
  });

  it('given there are posts, displays them', () => {
    // ARRANGE
    const recentTopic = createRecentActiveForumTopic();

    render<App.Community.Data.UserRecentPostsPageProps>(<UserPostsMainRoot />, {
      pageProps: {
        targetUser: createUser(),
        paginatedTopics: createPaginatedData([recentTopic]),
      },
    });

    // ASSERT
    expect(screen.getAllByText(recentTopic.title)[0]).toBeVisible();
  });
});
