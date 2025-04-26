import userEvent from '@testing-library/user-event';
import dayjs from 'dayjs';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import { createForumTopicComment } from '@/test/factories';

import { ForumPostCardTimestamps } from './ForumPostCardTimestamps';

// Suppress validateDOMNesting() errors.
console.error = vi.fn();

describe('Component: ForumPostCardTimestamps', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <ForumPostCardTimestamps
        comment={createForumTopicComment({
          id: 1,
          createdAt: dayjs.utc().subtract(2, 'days').toISOString(),
          updatedAt: dayjs.utc().subtract(2, 'days').toISOString(),
        })}
      />,
      { pageProps: { auth: { user: createAuthenticatedUser() } } },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a comment was created within the last 24 hours, shows relative time', async () => {
    // ARRANGE
    const createdAt = dayjs.utc().subtract(2, 'hours').toISOString();

    render(
      <ForumPostCardTimestamps
        comment={createForumTopicComment({ id: 1, createdAt, updatedAt: createdAt })}
      />,
      {
        pageProps: {
          auth: {
            user: createAuthenticatedUser({
              preferences: {
                prefersAbsoluteDates: false,
                shouldAlwaysBypassContentWarnings: false,
              },
            }),
          },
        },
      },
    );

    // ASSERT
    expect(screen.getByText(/2 hours ago/i)).toBeVisible();
  });

  it('given a comment was created within the last 24 hours, shows absolute time in a tooltip', async () => {
    // ARRANGE
    const createdAt = dayjs.utc().subtract(2, 'hours').toISOString();

    render(
      <ForumPostCardTimestamps
        comment={createForumTopicComment({ id: 1, createdAt, updatedAt: createdAt })}
      />,
      {
        pageProps: {
          auth: {
            user: createAuthenticatedUser({
              preferences: {
                prefersAbsoluteDates: false,
                shouldAlwaysBypassContentWarnings: false,
              },
            }),
          },
        },
      },
    );

    // ACT
    const trigger = screen.getByText(/2 hours ago/i);
    await userEvent.hover(trigger);

    await waitFor(() => {
      expect(screen.getByRole('tooltip')).toBeVisible();
    });
  });

  it('given the user prefers absolute dates, shows absolute time without tooltip', () => {
    // ARRANGE
    const createdAt = dayjs.utc().subtract(2, 'hours').toISOString();

    render(
      <ForumPostCardTimestamps
        comment={createForumTopicComment({ id: 1, createdAt, updatedAt: createdAt })}
      />,
      {
        pageProps: {
          auth: {
            user: createAuthenticatedUser({
              preferences: {
                prefersAbsoluteDates: true, // !!
                shouldAlwaysBypassContentWarnings: false,
              },
            }),
          },
        },
      },
    );

    // ASSERT
    // ... should show date in MMM DD, YYYY, HH:mm format ...
    expect(screen.getByText(/\w+ \d{2}, \d{4}, \d{2}:\d{2}/)).toBeVisible();
    expect(screen.queryByRole('tooltip')).not.toBeInTheDocument();
  });

  it('given a comment was edited, shows both creation and edit times', () => {
    // ARRANGE
    vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

    const createdAt = dayjs.utc().subtract(2, 'days').toISOString();
    const updatedAt = dayjs.utc().subtract(1, 'day').toISOString();

    render(
      <ForumPostCardTimestamps
        comment={createForumTopicComment({ id: 1, createdAt, updatedAt })}
      />,
      { pageProps: { auth: { user: createAuthenticatedUser() } } },
    );

    // ASSERT
    expect(screen.getByText(/edited/i)).toBeVisible();

    expect(screen.getByText(/Oct 23, 2023/i)).toBeVisible();
    expect(screen.getByText(/Oct 24, 2023/i)).toBeVisible();
  });

  it('given a date is null, does not crash', () => {
    // ARRANGE
    const { container } = render(
      <ForumPostCardTimestamps
        comment={createForumTopicComment({
          id: 1,
          createdAt: null as any,
          updatedAt: null,
        })}
      />,
      { pageProps: { auth: { user: createAuthenticatedUser() } } },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });
});
