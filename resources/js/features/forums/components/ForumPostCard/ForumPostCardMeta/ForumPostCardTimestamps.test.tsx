import userEvent from '@testing-library/user-event';
import dayjs from 'dayjs';

import { createAuthenticatedUser, createAuthenticatedUserPreferences } from '@/common/models';
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
              preferences: createAuthenticatedUserPreferences({
                prefersAbsoluteDates: false,
                shouldAlwaysBypassContentWarnings: false,
              }),
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
              preferences: createAuthenticatedUserPreferences({
                prefersAbsoluteDates: false,
                shouldAlwaysBypassContentWarnings: false,
              }),
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
              preferences: createAuthenticatedUserPreferences({
                prefersAbsoluteDates: true, // !!
                shouldAlwaysBypassContentWarnings: false,
              }),
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

  it('given a comment was edited at least 2 minutes after the create date, shows both creation and edit times', () => {
    // ARRANGE
    vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

    const createdAt = dayjs.utc().subtract(2, 'days').toISOString();
    const editedAt = dayjs.utc(createdAt).add(1, 'day').toISOString(); // !!

    render(
      <ForumPostCardTimestamps comment={createForumTopicComment({ id: 1, createdAt, editedAt })} />,
      { pageProps: { auth: { user: createAuthenticatedUser() } } },
    );

    // ASSERT
    expect(screen.getByText(/edited/i)).toBeVisible();

    expect(screen.getByText(/Oct 23, 2023/i)).toBeVisible();
    expect(screen.getByText(/Oct 24, 2023/i)).toBeVisible();
  });

  it('given a comment was edited within the last 24 hours, shows absolute edit time in a tooltip', async () => {
    // ARRANGE
    vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

    const createdAt = dayjs.utc().subtract(3, 'days').toISOString();
    const editedAt = dayjs.utc().subtract(2, 'hours').toISOString(); // !!

    render(
      <ForumPostCardTimestamps comment={createForumTopicComment({ id: 1, createdAt, editedAt })} />,
      {
        pageProps: {
          auth: {
            user: createAuthenticatedUser({
              preferences: createAuthenticatedUserPreferences({
                prefersAbsoluteDates: false,
                shouldAlwaysBypassContentWarnings: false,
              }),
            }),
          },
        },
      },
    );

    // ACT
    await userEvent.hover(screen.getByText(/2 hours ago/i));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByRole('tooltip')).toHaveTextContent('Oct 24, 2023 10:00 PM');
    });
  });

  it('given a comment was not edited at least 2 minutes after the create date, shows both creation and edit times', () => {
    // ARRANGE
    vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

    const createdAt = dayjs.utc().subtract(2, 'days').toISOString();
    const editedAt = dayjs.utc(createdAt).add(1, 'second').toISOString(); // !!

    render(
      <ForumPostCardTimestamps comment={createForumTopicComment({ id: 1, createdAt, editedAt })} />,
      { pageProps: { auth: { user: createAuthenticatedUser() } } },
    );

    // ASSERT
    expect(screen.queryByText(/edited/i)).not.toBeInTheDocument();
  });

  it('given a comment was never edited but was written to long after creation, does not show an edit label', () => {
    // ARRANGE
    vi.setSystemTime(dayjs.utc('2023-10-25').toDate());

    const createdAt = dayjs.utc().subtract(5, 'days').toISOString();
    const updatedAt = dayjs.utc(createdAt).add(3, 'days').toISOString(); // !!

    render(
      <ForumPostCardTimestamps
        comment={createForumTopicComment({ id: 1, createdAt, updatedAt, editedAt: null })}
      />,
      { pageProps: { auth: { user: createAuthenticatedUser() } } },
    );

    // ASSERT
    expect(screen.queryByText(/edited/i)).not.toBeInTheDocument();
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
