import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';
import axios from 'axios';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createAchievement, createComment } from '@/test/factories';

import { AchievementCommentList } from './AchievementCommentList';

describe('Component: AchievementCommentList', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const achievement = createAchievement();
    const { container } = render(<AchievementCommentList />, {
      pageProps: {
        achievement,
        can: { createAchievementComments: false },
        isSubscribedToComments: false,
        numComments: 5,
        recentVisibleComments: [],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given the achievement has comments, displays those comments', () => {
    // ARRANGE
    const comments = [
      createComment({ payload: 'First comment' }),
      createComment({ payload: 'Second comment' }),
    ];

    const achievement = createAchievement();
    render(<AchievementCommentList />, {
      pageProps: {
        achievement,
        can: { createAchievementComments: false },
        isSubscribedToComments: false,
        numComments: comments.length,
        recentVisibleComments: comments,
      },
    });

    // ASSERT
    expect(screen.getByText(/first comment/i)).toBeVisible();
    expect(screen.getByText(/second comment/i)).toBeVisible();
  });

  it('given there are more than 20 comments, shows a link to view all comments', () => {
    // ARRANGE
    const achievement = createAchievement({ id: 789 });
    const comments = [createComment()];

    render(<AchievementCommentList />, {
      pageProps: {
        achievement,
        can: { createAchievementComments: false },
        isSubscribedToComments: false,
        numComments: 25,
        recentVisibleComments: comments,
      },
    });

    // ASSERT
    const allCommentsLink = screen.getByRole('link', { name: /all 25/i });
    expect(allCommentsLink).toBeVisible();
    expect(allCommentsLink).toHaveAttribute(
      'href',
      expect.stringContaining('achievement.comment.index'),
    );
  });

  it('given there are fewer than 21 comments, does not show the view all link', () => {
    // ARRANGE
    const achievement = createAchievement();
    render(<AchievementCommentList />, {
      pageProps: {
        achievement,
        can: { createAchievementComments: false },
        isSubscribedToComments: false,
        numComments: 20,
        recentVisibleComments: [createComment()],
      },
    });

    // ASSERT
    expect(screen.queryByText(/all 20/i)).not.toBeInTheDocument();
  });

  it('given the user has permission to comment, allows them to add comments', () => {
    // ARRANGE
    const achievement = createAchievement();
    render(<AchievementCommentList />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        achievement,
        can: { createAchievementComments: true },
        isSubscribedToComments: false,
        numComments: 5,
        recentVisibleComments: [createComment()],
      },
    });

    // ASSERT
    expect(screen.getByRole('textbox')).toBeVisible();
  });

  it('given the user does not have permission to comment, does not show the comment form', () => {
    // ARRANGE
    const achievement = createAchievement();
    render(<AchievementCommentList />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        achievement,
        can: { createAchievementComments: false },
        isSubscribedToComments: false,
        numComments: 5,
        recentVisibleComments: [createComment()],
      },
    });

    // ASSERT
    expect(screen.queryByRole('textbox')).not.toBeInTheDocument();
  });

  it('when a comment is deleted, reloads the comments list', async () => {
    // ARRANGE
    vi.spyOn(axios, 'delete').mockResolvedValueOnce({ success: true });
    vi.spyOn(window, 'confirm').mockReturnValue(true);
    const reloadSpy = vi.spyOn(router, 'reload').mockImplementation(vi.fn());

    const achievement = createAchievement();
    render(<AchievementCommentList />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        achievement,
        can: { createAchievementComments: true },
        isSubscribedToComments: false,
        numComments: 5,
        recentVisibleComments: [createComment({ canDelete: true })],
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /delete/i }));

    // ASSERT
    expect(reloadSpy).toHaveBeenCalledWith({ only: ['recentVisibleComments'] });
  });

  it('when a new comment is submitted, reloads the comments list', async () => {
    // ARRANGE
    vi.spyOn(axios, 'post').mockResolvedValueOnce({ success: true });
    const reloadSpy = vi.spyOn(router, 'reload').mockImplementation(vi.fn());

    const achievement = createAchievement();
    render(<AchievementCommentList />, {
      pageProps: {
        auth: { user: createAuthenticatedUser() },
        achievement,
        can: { createAchievementComments: true },
        isSubscribedToComments: false,
        numComments: 5,
        recentVisibleComments: [],
      },
    });

    // ACT
    const commentForm = screen.getByRole('textbox');
    await userEvent.type(commentForm, 'New test comment');
    await userEvent.click(screen.getByRole('button', { name: /submit/i }));

    // ASSERT
    expect(reloadSpy).toHaveBeenCalledWith({ only: ['recentVisibleComments'] });
  });
});
