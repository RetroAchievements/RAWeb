import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { render, screen, waitFor } from '@/test';
import { createForum, createForumCategory } from '@/test/factories';

import { CreateTopicForm } from './CreateTopicForm';

// Prevent the autosize textarea from flooding the console with errors.
window.scrollTo = vi.fn();

// Suppress "Error: Not implemented: navigation (except hash changes)".
console.error = vi.fn();

describe('Component: CreateTopicForm', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<CreateTopicForm onPreview={vi.fn()} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user has not entered any text, disables the preview and submit buttons', () => {
    // ARRANGE
    render(<CreateTopicForm onPreview={vi.fn()} />);

    // ASSERT
    expect(screen.getByRole('button', { name: /preview/i })).toBeDisabled();
    expect(screen.getByRole('button', { name: /submit/i })).toBeDisabled();
  });

  it('given the user enters text in both fields, enables the preview and submit buttons', async () => {
    // ARRANGE
    render(<CreateTopicForm onPreview={vi.fn()} />);

    // ACT
    await userEvent.type(
      screen.getByPlaceholderText(/enter your new topic's title/i),
      'Test Title',
    );
    await userEvent.type(screen.getByPlaceholderText(/don't ask for links/i), 'Hello world');

    // ASSERT
    expect(screen.getByRole('button', { name: /preview/i })).toBeEnabled();
    expect(screen.getByRole('button', { name: /submit/i })).toBeEnabled();
  });

  it('given the user clicks preview, calls the preview callback with the current text', async () => {
    // ARRANGE
    const previewSpy = vi.fn();

    render(<CreateTopicForm onPreview={previewSpy} />);

    // ACT
    await userEvent.type(
      screen.getByPlaceholderText(/enter your new topic's title/i),
      'Test Title',
    );
    await userEvent.type(screen.getByPlaceholderText(/don't ask for links/i), 'Hello world');
    await userEvent.click(screen.getByRole('button', { name: /preview/i }));

    // ASSERT
    expect(previewSpy).toHaveBeenCalledWith('Hello world');
  });

  it('given the title is too short, displays an error message', async () => {
    // ARRANGE
    render(<CreateTopicForm onPreview={vi.fn()} />, {
      pageProps: { forum: createForum() },
    });

    // ACT
    await userEvent.type(screen.getByPlaceholderText(/enter your new topic's title/i), 'A');
    await userEvent.type(screen.getByPlaceholderText(/don't ask for links/i), 'Test Body');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    expect(screen.getByRole('button', { name: /submit/i })).toBeDisabled();
  });

  it('given the user submits the form, makes the correct POST call to the server', async () => {
    // ARRANGE
    vi.spyOn(router, 'visit').mockImplementationOnce(vi.fn());

    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: { success: true, newTopicId: 789 },
    });

    const category = createForumCategory();
    const forum = createForum({ category });

    render(<CreateTopicForm onPreview={vi.fn()} />, {
      pageProps: { forum },
    });

    // ACT
    await userEvent.type(
      screen.getByPlaceholderText(/enter your new topic's title/i),
      'Test Title',
    );
    await userEvent.type(screen.getByPlaceholderText(/don't ask for links/i), 'Test Body');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(postSpy).toHaveBeenCalledWith(
        route('api.forum-topic.store', { category: category.id, forum: forum.id }),
        {
          title: 'Test Title',
          body: 'Test Body',
        },
      );
    });
  });

  it('given the form submission succeeds, redirects to the new topic', async () => {
    // ARRANGE
    const visitSpy = vi.spyOn(router, 'visit').mockImplementationOnce(vi.fn());

    vi.spyOn(axios, 'post').mockResolvedValueOnce({
      data: { success: true, newTopicId: 789 },
    });

    const category = createForumCategory();
    const forum = createForum({ category });

    render(<CreateTopicForm onPreview={vi.fn()} />, {
      pageProps: { forum },
    });

    // ACT
    await userEvent.type(
      screen.getByPlaceholderText(/enter your new topic's title/i),
      'Test Title',
    );
    await userEvent.type(screen.getByPlaceholderText(/don't ask for links/i), 'Test Body');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    await waitFor(() => {
      expect(visitSpy).toHaveBeenCalledWith(['forum-topic.show', { topic: 789 }]);
    });
  });

  it('shows the current character count and maximum allowed characters', async () => {
    // ARRANGE
    render(<CreateTopicForm onPreview={vi.fn()} />, {
      pageProps: { forum: createForum() },
    });

    // ACT
    await userEvent.type(screen.getByPlaceholderText(/don't ask for links/i), 'Hello');

    // ASSERT
    expect(screen.getByText(/5.*60,000/)).toBeVisible();
  });
});
