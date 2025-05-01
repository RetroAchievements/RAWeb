import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createMessage, createUser } from '@/test/factories';

import { ReadableMessageCard } from './ReadableMessageCard';

describe('Component: ReadableMessageCard', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const message = createMessage({
      author: createUser(),
      body: 'Hello world',
      createdAt: '2024-01-01T00:00:00Z',
    });

    const { container } = render(<ReadableMessageCard message={message} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given a message with an author, displays their avatar and metadata', () => {
    // ARRANGE
    const author = createUser({ displayName: 'TestUser' });
    const message = createMessage({
      author,
      body: 'Hello world',
      createdAt: '2024-01-01T00:00:00Z',
    });

    render(<ReadableMessageCard message={message} />);

    // ASSERT
    expect(screen.getByRole('img', { name: /testuser/i })).toBeVisible();
  });

  it('given a message with a relative date preference, displays the date in relative format', () => {
    // ARRANGE
    const message = createMessage({
      author: createUser(),
      body: 'Hello world',
      createdAt: '2024-01-01T00:00:00Z',
    });

    render(<ReadableMessageCard message={message} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            preferences: { prefersAbsoluteDates: false, shouldAlwaysBypassContentWarnings: false },
          }),
        },
      },
    });

    // ASSERT
    expect(screen.getByText(/January 1, 2024/i)).toBeVisible();
  });

  it('given a message with an absolute date preference, displays the date in absolute format', () => {
    // ARRANGE
    const message = createMessage({
      author: createUser(),
      body: 'Hello world',
      createdAt: '2024-01-01T00:00:00Z',
    });

    render(<ReadableMessageCard message={message} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            preferences: { prefersAbsoluteDates: true, shouldAlwaysBypassContentWarnings: false },
          }),
        },
      },
    });

    // ASSERT
    expect(screen.getByText(/Jan 01, 2024/i)).toBeVisible();
  });

  it('given a message with a body, renders it using the shortcode renderer', () => {
    // ARRANGE
    const message = createMessage({
      author: createUser(),
      body: '[code]test code[/code]',
      createdAt: '2024-01-01T00:00:00Z',
    });

    render(<ReadableMessageCard message={message} />);

    // ASSERT
    const textEl = screen.getByText(/test code/i);
    expect(textEl).toBeVisible();
    expect(textEl).toHaveClass('codetags');
  });

  it('given a message includes sentBy metadata, shows the real sender display name', () => {
    // ARRANGE
    const message = createMessage({
      author: createUser({ displayName: 'RAdmin' }),
      body: 'Hello world',
      createdAt: '2024-01-01T00:00:00Z',
      sentBy: createUser({ displayName: 'Scott' }),
    });

    render(<ReadableMessageCard message={message} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            preferences: { prefersAbsoluteDates: false, shouldAlwaysBypassContentWarnings: false },
          }),
        },
      },
    });

    // ASSERT
    expect(screen.getByText(/sent by/i)).toBeVisible();
    expect(screen.getByRole('link', { name: /scott/i })).toBeVisible();
  });
});
