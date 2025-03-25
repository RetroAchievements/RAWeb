import dayjs from 'dayjs';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';

import { MessageTimestamp } from './MessageTimestamp';

describe('Component: MessageTimestamp', () => {
  // Store a reference date to ensure consistent testing.
  const NOW = '2024-02-01T12:00:00Z';

  beforeEach(() => {
    vi.setSystemTime(new Date(NOW));
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const message = {
      createdAt: NOW,
    } as App.Community.Data.Message;

    const { container } = render(<MessageTimestamp message={message} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user prefers absolute dates, shows the absolute date format', () => {
    // ARRANGE
    const message = {
      createdAt: NOW,
    } as App.Community.Data.Message;

    const user = createAuthenticatedUser({
      preferences: { prefersAbsoluteDates: true, shouldAlwaysBypassContentWarnings: false },
    });

    render(<MessageTimestamp message={message} />, {
      pageProps: { auth: { user } },
    });

    // ASSERT
    expect(screen.getByText(/feb 01, 2024, 12:00/i)).toBeVisible();
  });

  it('given the message is older than a month, shows the absolute date regardless of user preference', () => {
    // ARRANGE
    const oldDate = dayjs(NOW).subtract(2, 'months').toISOString();
    const message = {
      createdAt: oldDate,
    } as App.Community.Data.Message;

    const user = createAuthenticatedUser({
      preferences: { prefersAbsoluteDates: false, shouldAlwaysBypassContentWarnings: true },
    });

    render(<MessageTimestamp message={message} />, {
      pageProps: { auth: { user } },
    });

    // ASSERT
    expect(screen.getByText(/december 1, 2023 12:00/i)).toBeVisible();
  });

  it('given a recent message and user preferring relative dates, shows relative time with absolute date on hover', () => {
    // ARRANGE
    const recentDate = dayjs(NOW).subtract(1, 'day').toISOString();
    const message = {
      createdAt: recentDate,
    } as App.Community.Data.Message;

    const user = createAuthenticatedUser({
      preferences: { prefersAbsoluteDates: false, shouldAlwaysBypassContentWarnings: false },
    });

    render(<MessageTimestamp message={message} />, {
      pageProps: { auth: { user } },
    });

    // ASSERT
    const timestampElement = screen.getByText(/1 day ago/i);
    expect(timestampElement).toBeVisible();
    expect(timestampElement).toHaveAttribute('title', expect.stringMatching(/january 31, 2024/i));
  });
});
