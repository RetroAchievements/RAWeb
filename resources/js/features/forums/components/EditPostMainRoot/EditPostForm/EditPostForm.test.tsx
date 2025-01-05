import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { render, screen, waitFor } from '@/test';
import { createForumTopic, createForumTopicComment } from '@/test/factories';

import { EditPostForm } from './EditPostForm';

describe('Component: EditPostForm', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<EditPostForm onPreview={vi.fn()} />, {
      pageProps: {
        forumTopicComment: createForumTopicComment(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user has not entered any text, disables the preview and submit buttons', () => {
    // ARRANGE
    render(<EditPostForm onPreview={vi.fn()} />, {
      pageProps: {
        forumTopicComment: createForumTopicComment({ body: '' }),
      },
    });

    // ASSERT
    expect(screen.getByRole('button', { name: /preview/i })).toBeDisabled();
    expect(screen.getByRole('button', { name: /submit/i })).toBeDisabled();
  });

  it('given the user enters text, enables the preview and submit buttons', async () => {
    // ARRANGE
    render(<EditPostForm onPreview={vi.fn()} />, {
      pageProps: {
        forumTopicComment: createForumTopicComment({ body: '' }),
      },
    });

    // ACT
    await userEvent.type(screen.getByPlaceholderText(/don't ask for links/i), 'Hello world');

    // ASSERT
    expect(screen.getByRole('button', { name: /preview/i })).toBeEnabled();
    expect(screen.getByRole('button', { name: /submit/i })).toBeEnabled();
  });

  it('given the user clicks preview, calls the preview callback with the current text', async () => {
    // ARRANGE
    const previewSpy = vi.fn();

    render(<EditPostForm onPreview={previewSpy} />, {
      pageProps: {
        forumTopicComment: createForumTopicComment({ body: '' }),
      },
    });

    // ACT
    await userEvent.type(screen.getByPlaceholderText(/don't ask for links/i), 'Hello world');
    await userEvent.click(screen.getByRole('button', { name: /preview/i }));

    // ASSERT
    expect(previewSpy).toHaveBeenCalledWith('Hello world');
  });

  it('given the user submits the form, makes the correct PATCH call to the server', async () => {
    // ARRANGE
    const comment = createForumTopicComment({ id: 123, body: 'Initial text' });
    const patchSpy = vi.spyOn(axios, 'patch').mockResolvedValueOnce({ data: {} });

    render(<EditPostForm onPreview={vi.fn()} />, {
      pageProps: {
        forumTopicComment: comment,
      },
    });

    // ACT
    await userEvent.clear(screen.getByPlaceholderText(/don't ask for links/i));
    await userEvent.type(screen.getByPlaceholderText(/don't ask for links/i), 'Updated text');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(patchSpy).toHaveBeenCalledWith(
        route('api.forum-topic-comment.update', { comment: comment.id }),
        { body: 'Updated text' },
      );
    });
  });

  it('given the form submission succeeds, shows a success message', async () => {
    // ARRANGE
    const mockLocationAssign = vi.fn();
    Object.defineProperty(window, 'location', {
      value: { assign: mockLocationAssign },
      writable: true,
    });

    const comment = createForumTopicComment({
      id: 123,
      body: 'Initial text',
      forumTopic: createForumTopic({ id: 1 }),
    });

    vi.spyOn(axios, 'patch').mockResolvedValueOnce({ data: {} });

    render(<EditPostForm onPreview={vi.fn()} />, {
      pageProps: {
        forumTopicComment: comment,
      },
    });

    // ACT
    await userEvent.clear(screen.getByPlaceholderText(/don't ask for links/i));
    await userEvent.type(screen.getByPlaceholderText(/don't ask for links/i), 'Updated text');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/updated/i)[0]).toBeVisible();
    });
  });

  it('given the form submission fails, shows an error message', async () => {
    // ARRANGE
    const comment = createForumTopicComment({ id: 123, body: 'Initial text' });
    vi.spyOn(axios, 'patch').mockRejectedValueOnce(new Error('API Error'));

    render(<EditPostForm onPreview={vi.fn()} />, {
      pageProps: {
        forumTopicComment: comment,
      },
    });

    // ACT
    await userEvent.clear(screen.getByPlaceholderText(/don't ask for links/i));
    await userEvent.type(screen.getByPlaceholderText(/don't ask for links/i), 'Updated text');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/something went wrong/i)).toBeVisible();
    });
  });

  it('shows the current character count and maximum allowed characters', () => {
    // ARRANGE
    const comment = createForumTopicComment({ body: 'Hello' });

    render(<EditPostForm onPreview={vi.fn()} />, {
      pageProps: {
        forumTopicComment: comment,
      },
    });

    // ASSERT
    expect(screen.getByText(/5.*60,000/)).toBeVisible();
  });
});
