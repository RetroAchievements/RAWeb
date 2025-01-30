import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createMessage, createMessageThread, createUser } from '@/test/factories';

import { MessagesCard } from './MessagesCard';

describe('Component: MessagesCard', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<MessagesCard messageThread={createMessageThread()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the thread is unread, displays the unread indicator', () => {
    // ARRANGE
    const thread = createMessageThread({
      isUnread: true, // !!
      participants: [createUser({ displayName: 'Other User' })],
    });

    render(<MessagesCard messageThread={thread} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
      },
    });

    // ASSERT
    expect(screen.getByText(/\(unread\)/i)).toBeVisible();
  });

  it('given the thread is read, does not display the unread indicator', () => {
    // ARRANGE
    const thread = createMessageThread({
      isUnread: false, // !!
      participants: [createUser({ displayName: 'Other User' })],
    });

    render(<MessagesCard messageThread={thread} />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser(),
        },
      },
    });

    // ASSERT
    expect(screen.queryByText(/\(unread\)/i)).not.toBeInTheDocument();
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

    render(<MessagesCard messageThread={thread} />, {
      pageProps: {
        auth: { user },
      },
    });

    // ASSERT
    expect(screen.getByText(/\d{4}/)).toBeVisible(); // we expect to see 4 digits for a year
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

    render(<MessagesCard messageThread={thread} />, {
      pageProps: {
        auth: { user: authUser },
      },
    });

    // ASSERT
    expect(screen.getByRole('img', { name: /first user/i })).toBeVisible();
  });
});
