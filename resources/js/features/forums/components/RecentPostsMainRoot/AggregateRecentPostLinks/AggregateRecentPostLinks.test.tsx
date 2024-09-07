import { render, screen } from '@/test';
import { createRecentActiveForumTopic } from '@/test/factories';

import { AggregateRecentPostLinks } from './AggregateRecentPostLinks';

describe('Component: AggregateRecentPostLinks', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <AggregateRecentPostLinks topic={createRecentActiveForumTopic()} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are not multiple posts in the topic for the day, shows nothing', () => {
    // ARRANGE
    render(
      <AggregateRecentPostLinks
        topic={createRecentActiveForumTopic({
          commentCount24h: 0,
          commentCount7d: 0,
        })}
      />,
    );

    // ASSERT
    expect(screen.queryByRole('link')).not.toBeInTheDocument();
  });

  it('given there are multiple posts in the last 24 hours, shows a link with the count', () => {
    // ARRANGE
    const recentActiveForumTopic = createRecentActiveForumTopic({
      id: 120,
      commentCount24h: 5,
      oldestComment24hId: 12345,
      commentCount7d: 5,
      oldestComment7dId: 99999,
    });

    render(<AggregateRecentPostLinks topic={recentActiveForumTopic} />);

    // ASSERT
    const linkEls = screen.getAllByRole('link');
    expect(linkEls.length).toEqual(1);

    const dailyLinkEl = screen.getByRole('link', { name: /5 posts in the last 24 hours/i });
    expect(dailyLinkEl).toBeVisible();
    expect(dailyLinkEl).toHaveAttribute('href', `/viewtopic.php?t=120&c=12345#12345`);
  });

  it('given there are more weekly posts than daily posts, shows both links', () => {
    // ARRANGE
    const recentActiveForumTopic = createRecentActiveForumTopic({
      id: 120,
      commentCount24h: 5,
      oldestComment24hId: 12345,
      commentCount7d: 8,
      oldestComment7dId: 99999,
    });

    render(<AggregateRecentPostLinks topic={recentActiveForumTopic} />);

    // ASSERT
    const linkEls = screen.getAllByRole('link');
    expect(linkEls.length).toEqual(2);

    const weeklyLinkEl = screen.getByRole('link', { name: /8 posts in the last 7 days/i });
    expect(weeklyLinkEl).toBeVisible();
    expect(weeklyLinkEl).toHaveAttribute('href', `/viewtopic.php?t=120&c=99999#99999`);
  });

  it('given there are no daily posts but there are weekly posts, shows the weekly link', () => {
    // ARRANGE
    const recentActiveForumTopic = createRecentActiveForumTopic({
      id: 120,
      commentCount24h: undefined,
      oldestComment24hId: undefined,
      commentCount7d: 8,
      oldestComment7dId: 99999,
    });

    render(<AggregateRecentPostLinks topic={recentActiveForumTopic} />);

    // ASSERT
    const linkEls = screen.getAllByRole('link');
    expect(linkEls.length).toEqual(1);

    const weeklyLinkEl = screen.getByRole('link', { name: /8 posts in the last 7 days/i });
    expect(weeklyLinkEl).toBeVisible();
    expect(weeklyLinkEl).toHaveAttribute('href', `/viewtopic.php?t=120&c=99999#99999`);
  });
});
