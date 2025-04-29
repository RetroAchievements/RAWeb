import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { render, screen } from '@/test';
import { createForumTopic } from '@/test/factories';

import { ToggleLockTopicButton } from './ToggleLockTopicButton';

describe('Component: ToggleLockTopicButton', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<ToggleLockTopicButton />, {
      pageProps: {
        forumTopic: createForumTopic(),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user cancels the confirmation dialog, does not make a toggle lock request', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => false);
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: {} });

    render(<ToggleLockTopicButton />, {
      pageProps: {
        forumTopic: createForumTopic(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /lock/i }));

    // ASSERT
    expect(postSpy).not.toHaveBeenCalled();
  });

  it('given the user confirms locking, makes the POST request and reloads on success', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true); // !!

    const reloadSpy = vi.spyOn(router, 'reload').mockImplementationOnce(vi.fn());
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: {} });

    render(<ToggleLockTopicButton />, {
      pageProps: {
        forumTopic: createForumTopic({ id: 1 }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: 'Lock' }));

    // ASSERT
    expect(postSpy).toHaveBeenCalledWith(route('api.forum-topic.toggle-lock', { topic: 1 }));
    expect(reloadSpy).toHaveBeenCalled();
  });

  it('given the topic is already locked, presents "Unlock" verbiage instead', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockImplementationOnce(() => true); // !!

    const reloadSpy = vi.spyOn(router, 'reload').mockImplementationOnce(vi.fn());
    const postSpy = vi.spyOn(axios, 'post').mockResolvedValueOnce({ data: {} });

    render(<ToggleLockTopicButton />, {
      pageProps: {
        forumTopic: createForumTopic({
          id: 1,
          lockedAt: new Date().toISOString(), // !!
        }),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /unlock/i }));

    // ASSERT
    expect(postSpy).toHaveBeenCalledWith(route('api.forum-topic.toggle-lock', { topic: 1 }));
    expect(reloadSpy).toHaveBeenCalled();
  });
});
