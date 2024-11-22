import { render, screen } from '@/test';
import { createPaginatedData, createRecentActiveForumTopic } from '@/test/factories';

import { RecentPostsMainRoot } from './RecentPostsMainRoot';

describe('Component: RecentPostsMainRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Community.Data.RecentPostsPageProps>(<RecentPostsMainRoot />, {
      pageProps: { paginatedTopics: createPaginatedData([]) },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays breadcrumbs', () => {
    // ARRANGE
    render<App.Community.Data.RecentPostsPageProps>(<RecentPostsMainRoot />, {
      pageProps: { paginatedTopics: createPaginatedData([]) },
    });

    // ASSERT
    expect(screen.getByRole('navigation', { name: /breadcrumb/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /forum index/i })).toBeVisible();
  });

  it('displays an accessible heading', () => {
    // ARRANGE
    render<App.Community.Data.RecentPostsPageProps>(<RecentPostsMainRoot />, {
      pageProps: { paginatedTopics: createPaginatedData([]) },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /recent posts/i })).toBeVisible();
  });

  it('given there are no posts, displays an empty state', () => {
    // ARRANGE
    render<App.Community.Data.RecentPostsPageProps>(<RecentPostsMainRoot />, {
      pageProps: { paginatedTopics: createPaginatedData([]) },
    });

    // ASSERT
    expect(screen.getByText(/no recent posts/i)).toBeVisible();
  });

  it('given there are recent posts, displays them', () => {
    // ARRANGE
    const recentPost = createRecentActiveForumTopic();

    render<App.Community.Data.RecentPostsPageProps>(<RecentPostsMainRoot />, {
      pageProps: { paginatedTopics: createPaginatedData([recentPost]) },
    });

    // ASSERT
    expect(screen.getAllByText(recentPost.title)[0]).toBeVisible();
  });
});
