import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import { createForumTopic } from '@/test/factories';

import { QuickReplyForm } from './QuickReplyForm';

// Suppress "Error: Not implemented: window.scrollTo".
console.error = vi.fn();

describe('Component: QuickReplyForm', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<QuickReplyForm onPreview={() => {}} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        forumTopic: createForumTopic(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there is no authenticated user, renders nothing', () => {
    // ARRANGE
    render(<QuickReplyForm onPreview={() => {}} />, {
      pageProps: {
        auth: null,
        forumTopic: createForumTopic(),
      },
    });

    // ASSERT
    expect(screen.queryByRole('form')).not.toBeInTheDocument();
  });

  it('given the user types a message and submits the form, creates a new forum topic comment', async () => {
    // ARRANGE
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: { commentId: 123 } });
    const mockAssign = vi.fn();
    Object.defineProperty(window, 'location', { value: { assign: mockAssign }, writable: true });

    const topic = createForumTopic();

    render(<QuickReplyForm onPreview={() => {}} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        forumTopic: topic,
      },
    });

    // ACT
    const textArea = screen.getByPlaceholderText(/don't ask for links/i);
    await userEvent.type(textArea, 'My message');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledWith(
        route('api.forum-topic-comment.create', { topic: topic.id }),
        { body: 'My message' },
      );
    });
  });

  it('given the user clicks preview, calls the preview handler with the current message', async () => {
    // ARRANGE
    const previewHandler = vi.fn();

    render(<QuickReplyForm onPreview={previewHandler} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        forumTopic: createForumTopic(),
      },
    });

    // ACT
    const textArea = screen.getByPlaceholderText(/don't ask for links/i);
    await userEvent.type(textArea, 'My message');
    await userEvent.click(screen.getByRole('button', { name: /preview/i }));

    // ASSERT
    expect(previewHandler).toHaveBeenCalledWith('My message');
  });

  it('given the message is empty, disables the preview button', () => {
    // ARRANGE
    render(<QuickReplyForm onPreview={() => {}} />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        forumTopic: createForumTopic(),
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /preview/i })).toBeDisabled();
  });
});
