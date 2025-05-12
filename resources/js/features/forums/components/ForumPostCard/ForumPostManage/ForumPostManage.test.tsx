import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';
import { route } from 'ziggy-js';

import { render, screen, waitFor } from '@/test';
import { createForumTopicComment, createUser } from '@/test/factories';

import { ForumPostManage } from './ForumPostManage';

describe('Component: ForumPostManage', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.spyOn(router, 'reload').mockImplementation(vi.fn());
  });

  it('renders without crashing', () => {
    // ARRANGE
    const comment = createForumTopicComment();

    const { container } = render(<ForumPostManage comment={comment} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the user confirms authorization, makes the API call and shows success toast', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockReturnValue(true);
    const putSpy = vi.spyOn(axios, 'put').mockResolvedValue({});

    const comment = createForumTopicComment({
      user: createUser({ displayName: 'TestUser' }),
    });

    render(<ForumPostManage comment={comment} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /authorize/i }));

    // ASSERT
    expect(putSpy).toHaveBeenCalledWith(route('api.user.forum-permissions.update'), {
      displayName: 'TestUser',
      isAuthorized: true,
    });

    await waitFor(() => {
      expect(screen.getByText(/authorized!/i)).toBeVisible();
    });

    expect(router.reload).toHaveBeenCalled();
  });

  it('given the user cancels authorization, does not make the API call', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockReturnValue(false);
    const putSpy = vi.spyOn(axios, 'put');

    const comment = createForumTopicComment();

    render(<ForumPostManage comment={comment} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /authorize/i }));

    // ASSERT
    expect(putSpy).not.toHaveBeenCalled();
  });

  it('given the user confirms blocking, makes the API call and shows success toast', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockReturnValue(true);
    const putSpy = vi.spyOn(axios, 'put').mockResolvedValue({});

    const comment = createForumTopicComment({
      user: createUser({ displayName: 'TestUser' }),
    });

    render(<ForumPostManage comment={comment} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /block/i }));

    // ASSERT
    expect(putSpy).toHaveBeenCalledWith(route('api.user.forum-permissions.update'), {
      displayName: 'TestUser',
      isAuthorized: false,
    });

    await waitFor(() => {
      expect(screen.getByText(/blocked!/i)).toBeVisible();
    });

    expect(router.reload).toHaveBeenCalled();
  });

  it('given the user cancels blocking, does not make the API call', async () => {
    // ARRANGE
    vi.spyOn(window, 'confirm').mockReturnValue(false);
    const putSpy = vi.spyOn(axios, 'put');

    const comment = createForumTopicComment();

    render(<ForumPostManage comment={comment} />);

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /block/i }));

    // ASSERT
    expect(putSpy).not.toHaveBeenCalled();
  });
});
