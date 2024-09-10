import { render, screen } from '@/test';
import { createPaginatedData, createRecentActiveForumTopic } from '@/test/factories';

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
});
