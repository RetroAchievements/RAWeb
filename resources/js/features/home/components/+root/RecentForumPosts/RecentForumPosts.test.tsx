import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import {
  createForumTopicComment,
  createHomePageProps,
  createRecentActiveForumTopic,
} from '@/test/factories';

import { RecentForumPosts } from './RecentForumPosts';

describe('Component: RecentForumPosts', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Http.Data.HomePageProps>(<RecentForumPosts />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays an accessible heading', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<RecentForumPosts />);

    // ASSERT
    expect(screen.getByRole('heading', { name: /recent forum posts/i })).toBeVisible();
  });

  it('displays an empty state if there are no forum posts', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<RecentForumPosts />);

    // ASSERT
    expect(screen.getByText(/no recent forum posts were found/i)).toBeVisible();
  });

  it('given there are recent forum posts, displays them', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<RecentForumPosts />, {
      pageProps: createHomePageProps({
        recentForumPosts: [
          createRecentActiveForumTopic({ title: 'My Great Topic' }),
          createRecentActiveForumTopic(),
          createRecentActiveForumTopic(),
          createRecentActiveForumTopic(),
        ],
      }),
    });

    // ASSERT
    expect(screen.getByRole('link', { name: /my great topic/i })).toBeVisible();
  });

  it('given there is no post payload in the topic item, does not crash', () => {
    // ARRANGE
    const { container } = render<App.Http.Data.HomePageProps>(<RecentForumPosts />, {
      pageProps: createHomePageProps({
        recentForumPosts: [createRecentActiveForumTopic({ latestComment: undefined })],
      }),
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user prefers seeing absolute dates, displays absolute dates', () => {
    // ARRANGE
    render<App.Http.Data.HomePageProps>(<RecentForumPosts />, {
      pageProps: {
        ...createHomePageProps({
          recentForumPosts: [
            createRecentActiveForumTopic({
              latestComment: createForumTopicComment({
                createdAt: new Date('2024-08-07').toISOString(),
              }),
            }),
          ],
        }),

        auth: {
          user: createAuthenticatedUser({
            preferences: { prefersAbsoluteDates: true, shouldAlwaysBypassContentWarnings: false },
          }),
        },
      },
    });

    // ASSERT
    expect(screen.getByText(/aug 07/i)).toBeVisible();
  });
});
