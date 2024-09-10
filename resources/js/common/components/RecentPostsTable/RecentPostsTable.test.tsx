import { render, screen } from '@/test';
import { createPaginatedData, createRecentActiveForumTopic } from '@/test/factories';

import { RecentPostsTable } from './RecentPostsTable';

describe('Component: RecentPostsTable', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<RecentPostsTable paginatedTopics={createPaginatedData([])} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('renders a table row for every given recent forum post', () => {
    // ARRANGE
    render(
      <RecentPostsTable
        paginatedTopics={createPaginatedData([
          createRecentActiveForumTopic(),
          createRecentActiveForumTopic(),
        ])}
      />,
    );

    // ASSERT
    expect(screen.getAllByRole('row').length).toEqual(3); // a header row and the two post rows
  });

  it('displays the topic title and the short message', () => {
    // ARRANGE
    const recentActiveForumTopic = createRecentActiveForumTopic();

    render(<RecentPostsTable paginatedTopics={createPaginatedData([recentActiveForumTopic])} />);

    // ASSERT
    expect(screen.getByText(recentActiveForumTopic.title)).toBeVisible();
    expect(screen.getByText(recentActiveForumTopic.latestComment.body)).toBeVisible();
  });
});
