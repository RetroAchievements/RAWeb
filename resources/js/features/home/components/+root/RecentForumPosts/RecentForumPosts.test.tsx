import { render, screen } from '@/test';

import { RecentForumPosts } from './RecentForumPosts';

describe('Component: RecentForumPosts', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<RecentForumPosts />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible heading', () => {
    // ARRANGE
    render(<RecentForumPosts />);

    // ASSERT
    expect(screen.getByRole('heading', { name: /recent forum posts/i })).toBeVisible();
  });

  it.todo('displays an empty state if there are no forum posts');
  it.todo('displays multiple forum post items');
  it.todo('displays the author display name in each row');
  it.todo('displays a timestamp for each post');
  it.todo('gives each post an accessible link');
  it.todo('displays the forum post preview content');

  it('has an accessible link to the forum Recent Posts page', () => {
    // ARRANGE
    render(<RecentForumPosts />);

    // ASSERT
    const linkEl = screen.getByRole('link', { name: /see more/i });

    expect(linkEl).toBeVisible();
    expect(linkEl).toHaveAttribute('href', 'forum.recent-posts');
  });
});
