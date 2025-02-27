import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { render, screen } from '@/test';
import { createForumTopic } from '@/test/factories';

import { DeleteTopicButton } from './DeleteTopicButton';

describe('Component: DeleteTopicButton', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<DeleteTopicButton />, {
      pageProps: {
        forumTopic: createForumTopic(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user cancels the confirmation dialog, does not make a delete request', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => false);
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ data: {} });

    render(<DeleteTopicButton />, {
      pageProps: {
        forumTopic: createForumTopic(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /delete permanently/i }));

    // ASSERT
    expect(deleteSpy).not.toHaveBeenCalled();
  });

  it('given the user confirms deletion, makes the delete request and redirects on success', async () => {
    // ARRANGE
    const mockLocationAssign = vi.fn();
    Object.defineProperty(window, 'location', {
      value: { assign: mockLocationAssign },
      writable: true,
    });

    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true);
    const deleteSpy = vi.spyOn(axios, 'delete').mockResolvedValueOnce({ data: {} });

    render(<DeleteTopicButton />, {
      pageProps: {
        forumTopic: createForumTopic({ id: 1 }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /delete permanently/i }));

    // ASSERT
    expect(deleteSpy).toHaveBeenCalledWith(route('api.forum-topic.destroy', { topic: 1 }));
  });
});
