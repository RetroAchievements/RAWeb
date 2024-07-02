import { createRecentForumPost, render, screen } from '@/test';

import { RecentPostsTable } from './RecentPostsTable';

describe('Component: RecentPostsTable', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<RecentPostsTable />, {
      pageProps: {
        recentForumPosts: [],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders a table row for every given recent forum post', () => {
    // ARRANGE
    render(<RecentPostsTable />, {
      pageProps: {
        recentForumPosts: [createRecentForumPost(), createRecentForumPost()],
      },
    });

    // ASSERT
    expect(screen.getAllByRole('row').length).toEqual(3); // a header row and the two post rows
  });

  it('displays the topic title and the short message', () => {
    // ARRANGE
    const recentForumPost = createRecentForumPost();

    render(<RecentPostsTable />, { pageProps: { recentForumPosts: [recentForumPost] } });

    // ASSERT
    expect(screen.getByText(recentForumPost.forumTopicTitle)).toBeVisible();
    expect(screen.getByText(recentForumPost.shortMessage)).toBeVisible();
  });
});
