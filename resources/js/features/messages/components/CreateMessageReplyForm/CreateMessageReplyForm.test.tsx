import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { createAuthenticatedUser } from '@/common/models';
import { __UNSAFE_VERY_DANGEROUS_SLEEP, render, screen, waitFor, within } from '@/test';
import { createMessageThread, createPaginatedData } from '@/test/factories';

import { CreateMessageReplyForm } from './CreateMessageReplyForm';

// Suppress window.scrollTo not implemented error.
console.error = vi.fn();

// Prevent the autosize textarea from flooding the console with errors.
window.scrollTo = vi.fn();

describe('Component: CreateMessageReplyForm', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const messageThread = createMessageThread();
    const paginatedMessages = createPaginatedData([], { lastPage: 5 });
    const mockOnPreview = vi.fn();

    const { container } = render(<CreateMessageReplyForm onPreview={mockOnPreview} />, {
      pageProps: {
        messageThread,
        paginatedMessages,
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user has not entered any text, disables the submit and preview buttons', () => {
    // ARRANGE
    const messageThread = createMessageThread();
    const paginatedMessages = createPaginatedData([], { lastPage: 5 });
    const mockOnPreview = vi.fn();

    render(<CreateMessageReplyForm onPreview={mockOnPreview} />, {
      pageProps: {
        messageThread,
        paginatedMessages,
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /submit/i })).toBeDisabled();
    expect(screen.getByRole('button', { name: /preview/i })).toBeDisabled();
  });

  it('given the user enters text, enables the preview and submit buttons', async () => {
    // ARRANGE
    const messageThread = createMessageThread();
    const paginatedMessages = createPaginatedData([], { lastPage: 5 });
    const mockOnPreview = vi.fn();

    render(<CreateMessageReplyForm onPreview={mockOnPreview} />, {
      pageProps: {
        messageThread,
        paginatedMessages,
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ACT
    await userEvent.type(screen.getByPlaceholderText(/enter your message here/i), 'test message');

    // ASSERT
    expect(screen.getByRole('button', { name: /submit/i })).toBeEnabled();
    expect(screen.getByRole('button', { name: /preview/i })).toBeEnabled();
  });

  it('given the user clicks preview, calls the onPreview callback with the current message text', async () => {
    // ARRANGE
    const messageThread = createMessageThread();
    const paginatedMessages = createPaginatedData([], { lastPage: 5 });
    const mockOnPreview = vi.fn();

    render(<CreateMessageReplyForm onPreview={mockOnPreview} />, {
      pageProps: {
        messageThread,
        paginatedMessages,
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ACT
    const messageText = 'test message';
    await userEvent.type(screen.getByPlaceholderText(/enter your message here/i), messageText);
    await userEvent.click(screen.getByRole('button', { name: /preview/i }));

    // ASSERT
    expect(mockOnPreview).toHaveBeenCalledWith(messageText);
  });

  it('given the user submits the form successfully, shows a success toast', async () => {
    // ARRANGE
    const messageThread = createMessageThread();
    const paginatedMessages = createPaginatedData([], { lastPage: 5 });
    const mockOnPreview = vi.fn();

    vi.spyOn(router, 'visit').mockImplementationOnce(vi.fn());

    vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: {} });

    render(<CreateMessageReplyForm onPreview={mockOnPreview} />, {
      pageProps: {
        messageThread,
        paginatedMessages,
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ACT
    await userEvent.type(screen.getByPlaceholderText(/enter your message here/i), 'test message');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/submitted/i)).toBeVisible();
    });

    // vitest is very unhappy when it hits the setTimeout() for router.visit().
    await __UNSAFE_VERY_DANGEROUS_SLEEP(1100);
  });

  it('given the user is muted, shows an error toast when trying to submit', async () => {
    // ARRANGE
    const messageThread = createMessageThread();
    const paginatedMessages = createPaginatedData([], { lastPage: 5 });
    const mockOnPreview = vi.fn();

    vi.spyOn(axios, 'post').mockRejectedValueOnce({
      response: { data: { error: 'muted_user' } },
    });

    render(<CreateMessageReplyForm onPreview={mockOnPreview} />, {
      pageProps: {
        messageThread,
        paginatedMessages,
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ACT
    await userEvent.type(screen.getByPlaceholderText(/enter your message here/i), 'test message');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/muted users can only message team accounts/i)).toBeVisible();
    });
  });

  it('given the target user is not accepting messages, shows an error toast when trying to submit', async () => {
    // ARRANGE
    const messageThread = createMessageThread();
    const paginatedMessages = createPaginatedData([], { lastPage: 5 });
    const mockOnPreview = vi.fn();

    vi.spyOn(axios, 'post').mockRejectedValueOnce({
      response: { data: { error: 'cannot_message_user' } },
    });

    render(<CreateMessageReplyForm onPreview={mockOnPreview} />, {
      pageProps: {
        messageThread,
        paginatedMessages,
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ACT
    await userEvent.type(screen.getByPlaceholderText(/enter your message here/i), 'test message');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/this user isn't accepting messages right now/i)).toBeVisible();
    });
  });

  it('given an unexpected error occurs, shows a generic error toast when trying to submit', async () => {
    // ARRANGE
    const messageThread = createMessageThread();
    const paginatedMessages = createPaginatedData([], { lastPage: 5 });
    const mockOnPreview = vi.fn();

    vi.spyOn(axios, 'post').mockRejectedValueOnce({
      response: { data: { error: 'unexpected_error' } },
    });

    render(<CreateMessageReplyForm onPreview={mockOnPreview} />, {
      pageProps: {
        messageThread,
        paginatedMessages,
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ACT
    await userEvent.type(screen.getByPlaceholderText(/enter your message here/i), 'test message');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/something went wrong/i)).toBeVisible();
    });
  });

  it('shows the current character count as the user types', async () => {
    // ARRANGE
    const messageThread = createMessageThread();
    const paginatedMessages = createPaginatedData([], { lastPage: 5 });
    const mockOnPreview = vi.fn();

    render(<CreateMessageReplyForm onPreview={mockOnPreview} />, {
      pageProps: {
        messageThread,
        paginatedMessages,
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ACT
    const messageText = 'test message';
    await userEvent.type(screen.getByPlaceholderText(/enter your message here/i), messageText);

    // ASSERT
    expect(screen.getByText(/12/i)).toBeVisible();
  });

  it('given the user presses Cmd+Enter while focused in the form, submits the form', async () => {
    // ARRANGE
    const messageThread = createMessageThread();
    const paginatedMessages = createPaginatedData([], { lastPage: 5 });
    const mockOnPreview = vi.fn();

    vi.spyOn(router, 'visit').mockImplementationOnce(vi.fn());
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: {} });

    render(<CreateMessageReplyForm onPreview={mockOnPreview} />, {
      pageProps: {
        messageThread,
        paginatedMessages,
        auth: { user: createAuthenticatedUser() },
      },
    });

    // ACT
    const textArea = screen.getByPlaceholderText(/enter your message here/i);
    await userEvent.type(textArea, 'test message');
    await userEvent.keyboard('{Meta>}{Enter}{/Meta}');

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalled();
    });
  });

  it('given the user is sending on behalf of a team account, shows the team avatar in the submit button', () => {
    // ARRANGE
    const user = createAuthenticatedUser({ displayName: 'Scott' });
    const messageThread = createMessageThread();
    const paginatedMessages = createPaginatedData([], { lastPage: 5 });
    const mockOnPreview = vi.fn();

    render(<CreateMessageReplyForm onPreview={mockOnPreview} />, {
      pageProps: {
        messageThread,
        paginatedMessages,
        auth: { user },
        senderUserDisplayName: 'RAdmin', // !!
        senderUserAvatarUrl: 'https://example.com/radmin-avatar.png', // !!
      },
    });

    // ASSERT
    const submitButton = screen.getByRole('button', { name: /submit/i });
    const avatarImage = within(submitButton).getByRole('img', { hidden: true });
    expect(avatarImage).toHaveAttribute('src', 'https://example.com/radmin-avatar.png');
    expect(avatarImage).toHaveAttribute('alt', 'RAdmin');
  });

  it('given the user is sending as themselves, does not show an avatar in the submit button', () => {
    // ARRANGE
    const user = createAuthenticatedUser({ displayName: 'Scott' });
    const messageThread = createMessageThread();
    const paginatedMessages = createPaginatedData([], { lastPage: 5 });
    const mockOnPreview = vi.fn();

    render(<CreateMessageReplyForm onPreview={mockOnPreview} />, {
      pageProps: {
        messageThread,
        paginatedMessages,
        auth: { user },
        senderUserDisplayName: 'Scott', // !! same as `user`
        senderUserAvatarUrl: 'https://example.com/scott-avatar.png',
      },
    });

    // ASSERT
    const submitButton = screen.getByRole('button', { name: /submit/i });
    const avatarImage = within(submitButton).queryByRole('img', { hidden: true });
    expect(avatarImage).not.toBeInTheDocument();
  });

  it('given no avatar URL is provided, does not show an avatar in the submit button', () => {
    // ARRANGE
    const user = createAuthenticatedUser({ displayName: 'Scott' });
    const messageThread = createMessageThread();
    const paginatedMessages = createPaginatedData([], { lastPage: 5 });
    const mockOnPreview = vi.fn();

    render(<CreateMessageReplyForm onPreview={mockOnPreview} />, {
      pageProps: {
        messageThread,
        paginatedMessages,
        auth: { user },
        senderUserDisplayName: 'RAdmin',
        senderUserAvatarUrl: null, // !! edge case, this is missing for some reason
      },
    });

    // ASSERT
    const submitButton = screen.getByRole('button', { name: /submit/i });
    const avatarImage = within(submitButton).queryByRole('img', { hidden: true });
    expect(avatarImage).not.toBeInTheDocument();
  });

  it('given the user is sending on behalf of a team account, shows "Submit as" text', () => {
    // ARRANGE
    const user = createAuthenticatedUser({ displayName: 'Scott' });
    const messageThread = createMessageThread();
    const paginatedMessages = createPaginatedData([], { lastPage: 5 });
    const mockOnPreview = vi.fn();

    render(<CreateMessageReplyForm onPreview={mockOnPreview} />, {
      pageProps: {
        messageThread,
        paginatedMessages,
        auth: { user },
        senderUserDisplayName: 'RAdmin', // !! different from `user`
        senderUserAvatarUrl: 'https://example.com/radmin-avatar.png',
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /submit as radmin/i })).toBeVisible();
  });

  it('given the user is sending as themselves, shows only "Submit" text', () => {
    // ARRANGE
    const user = createAuthenticatedUser({ displayName: 'Scott' });
    const messageThread = createMessageThread();
    const paginatedMessages = createPaginatedData([], { lastPage: 5 });
    const mockOnPreview = vi.fn();

    render(<CreateMessageReplyForm onPreview={mockOnPreview} />, {
      pageProps: {
        messageThread,
        paginatedMessages,
        auth: { user },
        senderUserDisplayName: 'Scott', // !! same as `user`
        senderUserAvatarUrl: 'https://example.com/scott-avatar.png',
      },
    });

    // ASSERT
    const submitButton = screen.getByRole('button', { name: /submit/i });
    expect(submitButton).toBeVisible();
    expect(submitButton).toHaveTextContent('Submit');
    expect(submitButton).not.toHaveTextContent('Submit as');
  });
});
