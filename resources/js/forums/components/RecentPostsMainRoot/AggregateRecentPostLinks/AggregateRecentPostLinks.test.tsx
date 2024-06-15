import { createRecentForumPost, render, screen } from '@/test';

import { AggregateRecentPostLinks } from './AggregateRecentPostLinks';

describe('Component: AggregateRecentPostLinks', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <AggregateRecentPostLinks recentForumPost={createRecentForumPost()} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are not multiple posts in the topic for the day, shows nothing', () => {
    // ARRANGE
    render(
      <AggregateRecentPostLinks
        recentForumPost={createRecentForumPost({
          commentCountDay: 0,
          commentCountWeek: 0,
        })}
      />,
    );

    // ASSERT
    expect(screen.queryByRole('link')).not.toBeInTheDocument();
  });

  it('given there are multiple posts in the last 24 hours, shows a link with the count', () => {
    // ARRANGE
    const recentForumPost = createRecentForumPost({
      forumTopicId: 120,
      commentCountDay: 5,
      commentIdDay: 12345,
      commentCountWeek: 5,
      commentIdWeek: 99999,
    });

    render(<AggregateRecentPostLinks recentForumPost={recentForumPost} />);

    // ASSERT
    const linkEls = screen.getAllByRole('link');
    expect(linkEls.length).toEqual(1);

    const dailyLinkEl = screen.getByRole('link', { name: /5 posts in the last 24 hours/i });
    expect(dailyLinkEl).toBeVisible();
    expect(dailyLinkEl).toHaveAttribute('href', `/viewtopic.php?t=120&c=12345#12345`);
  });

  it('given there are more weekly posts than daily posts, shows both links', () => {
    // ARRANGE
    const recentForumPost = createRecentForumPost({
      forumTopicId: 120,
      commentCountDay: 5,
      commentIdDay: 12345,
      commentCountWeek: 8,
      commentIdWeek: 99999,
    });

    render(<AggregateRecentPostLinks recentForumPost={recentForumPost} />);

    // ASSERT
    const linkEls = screen.getAllByRole('link');
    expect(linkEls.length).toEqual(2);

    const weeklyLinkEl = screen.getByRole('link', { name: /8 posts in the last 7 days/i });
    expect(weeklyLinkEl).toBeVisible();
    expect(weeklyLinkEl).toHaveAttribute('href', `/viewtopic.php?t=120&c=99999#99999`);
  });
});
