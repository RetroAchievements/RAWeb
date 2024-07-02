import { render, screen } from '@/test';

import { RecentPostsBreadcrumbs } from './RecentPostsBreadcrumbs';

describe('Component: RecentPostsBreadcrumbs', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<RecentPostsBreadcrumbs />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('has a link back to the forum index', () => {
    // ARRANGE
    render(<RecentPostsBreadcrumbs />);

    // ASSERT
    const forumIndexLinkEl = screen.getByRole('link', { name: /forum index/i });
    expect(forumIndexLinkEl).toBeVisible();
    expect(forumIndexLinkEl).toHaveAttribute('href', '/forum.php');
  });

  it('communicates the active link in an accessible manner', () => {
    // ARRANGE
    render(<RecentPostsBreadcrumbs />);

    // ASSERT
    const activeLinkEl = screen.getByRole('link', { name: /recent posts/i });

    expect(activeLinkEl).toBeVisible();
    expect(activeLinkEl).toHaveAttribute('aria-disabled', 'true');
    expect(activeLinkEl).toHaveAttribute('aria-current', 'page');
  });
});
