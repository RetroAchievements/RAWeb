import { render, screen } from '@/test';

import { ForumBreadcrumbs } from './ForumBreadcrumbs';

describe('Component: ForumBreadcrumbs', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ForumBreadcrumbs currentPageLabel="Recent Posts" />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('has a link back to the forum index', () => {
    // ARRANGE
    render(<ForumBreadcrumbs currentPageLabel="Recent Posts" />);

    // ASSERT
    const forumIndexLinkEl = screen.getByRole('link', { name: /forum index/i });
    expect(forumIndexLinkEl).toBeVisible();
    expect(forumIndexLinkEl).toHaveAttribute('href', '/forum.php');
  });

  it('communicates the active link in an accessible manner', () => {
    // ARRANGE
    render(<ForumBreadcrumbs currentPageLabel="Recent Posts" />);

    // ASSERT
    const activeLinkEl = screen.getByRole('link', { name: /recent posts/i });

    expect(activeLinkEl).toBeVisible();
    expect(activeLinkEl).toHaveAttribute('aria-disabled', 'true');
    expect(activeLinkEl).toHaveAttribute('aria-current', 'page');
  });
});
