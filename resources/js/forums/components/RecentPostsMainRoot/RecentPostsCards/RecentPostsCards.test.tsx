import { createRecentForumPost, render, screen } from '@/test';

import { RecentPostsCards } from './RecentPostsCards';

describe('Component: RecentPostsCards', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<RecentPostsCards />, {
      pageProps: {
        recentForumPosts: [],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders a card for every given recent forum post', () => {
    // ARRANGE
    render(<RecentPostsCards />, {
      pageProps: {
        recentForumPosts: [createRecentForumPost(), createRecentForumPost()],
      },
    });

    // ASSERT
    expect(screen.getAllByRole('img').length).toEqual(2); // test the presence of user avatars
  });

  it('displays the topic title and the short message', () => {
    // ARRANGE
    const recentForumPost = createRecentForumPost();

    render(<RecentPostsCards />, { pageProps: { recentForumPosts: [recentForumPost] } });

    // ASSERT
    expect(screen.getByText(recentForumPost.forumTopicTitle)).toBeVisible();
    expect(screen.getByText(recentForumPost.shortMessage)).toBeVisible();
  });
});
