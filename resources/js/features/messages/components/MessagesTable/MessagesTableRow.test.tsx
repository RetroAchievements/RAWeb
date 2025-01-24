import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createMessage, createMessageThread, createUser } from '@/test/factories';

import { MessagesTableRow } from './MessagesTableRow';

// Suppress invalid DOM nesting errors. They aren't relevant here.
console.error = vi.fn();

describe('Component: MessagesTableRow', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const thread = createMessageThread({
      isUnread: true,
      participants: [createUser({ displayName: 'Other User' })],
    });

    const { container } = render(<MessagesTableRow messageThread={thread} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the thread is unread, displays the unread indicator and bold text', () => {
    // ARRANGE
    const thread = createMessageThread({
      isUnread: true,
      participants: [createUser({ displayName: 'Other User' })],
    });

    render(<MessagesTableRow messageThread={thread} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
      },
    });

    // ASSERT
    expect(screen.getByText(/\(unread\)/i)).toBeVisible();
    expect(screen.getByRole('row')).toHaveClass('font-bold');
  });

  it('given the thread is read, does not display the unread indicator or bold text', () => {
    // ARRANGE
    const thread = createMessageThread({
      isUnread: false, // !!
      participants: [createUser({ displayName: 'Other User' })],
    });

    render(<MessagesTableRow messageThread={thread} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
      },
    });

    // ASSERT
    expect(screen.queryByText(/\(unread\)/i)).not.toBeInTheDocument();
    expect(screen.getByRole('row')).not.toHaveClass('font-bold');
  });

  it('given the user prefers absolute dates, displays the timestamp in absolute format', () => {
    // ARRANGE
    const message = createMessage();
    const thread = createMessageThread({
      messages: [message],
      participants: [createUser({ displayName: 'Other User' })],
    });
    const user = createAuthenticatedUser({
      preferences: {
        prefersAbsoluteDates: true, // !!
        shouldAlwaysBypassContentWarnings: true,
      },
    });

    render(<MessagesTableRow messageThread={thread} />, {
      pageProps: {
        auth: { user },
      },
    });

    // ASSERT
    expect(screen.getByText(/\d{4}/)).toBeVisible(); // we expect to see 4 digits for a year
  });

  it('displays the message count', () => {
    // ARRANGE
    const thread = createMessageThread({
      numMessages: 5, // !!
      participants: [createUser({ displayName: 'Other User' })],
    });

    render(<MessagesTableRow messageThread={thread} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
      },
    });

    // ASSERT
    expect(screen.getByText(/5/i)).toBeVisible();
  });

  it('displays the other participant avatar', () => {
    // ARRANGE
    const otherUser = createUser({ displayName: 'Other User' });
    const thread = createMessageThread({
      participants: [otherUser],
    });

    render(<MessagesTableRow messageThread={thread} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
      },
    });

    // ASSERT
    expect(screen.getByRole('img', { name: /other user/i })).toBeVisible();
  });

  it('given no other participant is found, uses the first participant as fallback', () => {
    // ARRANGE
    const firstParticipant = createUser({ displayName: 'First User' });
    const thread = createMessageThread({
      participants: [firstParticipant],
    });
    const authUser = createAuthenticatedUser({
      displayName: firstParticipant.displayName,
    });

    render(<MessagesTableRow messageThread={thread} />, {
      pageProps: {
        auth: { user: authUser },
      },
    });

    // ASSERT
    expect(screen.getByRole('img', { name: /first user/i })).toBeVisible();
  });
});
