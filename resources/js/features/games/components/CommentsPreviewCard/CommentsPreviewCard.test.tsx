import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen } from '@/test';
import { createComment, createUser, createZiggyProps } from '@/test/factories';

import { currentTabAtom } from '../../state/games.atoms';
import { CommentsPreviewCard } from './CommentsPreviewCard';

describe('Component: CommentsPreviewCard', () => {
  beforeEach(() => {
    vi.spyOn(router, 'replace').mockImplementation(vi.fn());
    vi.spyOn(router, 'visit').mockImplementation(vi.fn());

    window.scrollTo = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<CommentsPreviewCard />, {
      pageProps: {
        numComments: 5,
        recentVisibleComments: [createComment()],
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given there are no comments, renders nothing', () => {
    // ARRANGE
    render(<CommentsPreviewCard />, {
      pageProps: {
        numComments: 0,
        recentVisibleComments: [],
      },
    });

    // ASSERT
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  it('given there are only automated comments, renders nothing', () => {
    // ARRANGE
    render(<CommentsPreviewCard />, {
      pageProps: {
        numComments: 3,
        recentVisibleComments: [
          createComment({ isAutomated: true }),
          createComment({ isAutomated: true }),
        ],
      },
    });

    // ASSERT
    expect(screen.queryByRole('button')).not.toBeInTheDocument();
  });

  it('displays the most recent non-automated comment preview', () => {
    // ARRANGE
    const user = createUser({ displayName: 'TestPlayer' });

    render(<CommentsPreviewCard />, {
      pageProps: {
        numComments: 5,
        recentVisibleComments: [
          createComment({
            user,
            payload: 'Older comment',
            createdAt: '2024-01-01', // !! older date
          }),
          createComment({
            user,
            payload: 'This is a great achievement set!',
            createdAt: '2025-06-06', // !! more recent date, so this should be shown
          }),
        ],
      },
    });

    // ASSERT
    expect(screen.getByText(/this is a great achievement set!/i)).toBeVisible();
  });

  it('filters out automated comments and shows the first non-automated one', () => {
    // ARRANGE
    const humanUser = createUser({ displayName: 'HumanPlayer' });

    render(<CommentsPreviewCard />, {
      pageProps: {
        numComments: 3,
        recentVisibleComments: [
          createComment({ isAutomated: true, payload: 'Automated message' }),
          createComment({ payload: 'Human comment here', user: humanUser }),
        ],
      },
    });

    // ASSERT
    expect(screen.queryByText(/automated message/i)).not.toBeInTheDocument();
    expect(screen.getByText(/human comment here/i)).toBeVisible();
    expect(screen.getByText('HumanPlayer')).toBeVisible();
  });

  it('displays the avatar stack with unique users', () => {
    // ARRANGE
    const user1 = createUser({ displayName: 'Player1' });
    const user2 = createUser({ displayName: 'Player2' });

    render(<CommentsPreviewCard />, {
      pageProps: {
        numComments: 5,
        recentVisibleComments: [
          createComment({ user: user1 }),
          createComment({ user: user2 }),
          createComment({ user: user1 }), // same user as first comment
        ],
        ziggy: createZiggyProps(),
      },
    });

    // ASSERT
    expect(screen.getAllByRole('img')).toHaveLength(2);
  });

  it('given the user prefers absolute dates, passes that preference to DiffTimestamp', () => {
    // ARRANGE
    render(<CommentsPreviewCard />, {
      pageProps: {
        auth: {
          user: createAuthenticatedUser({
            preferences: { prefersAbsoluteDates: true, shouldAlwaysBypassContentWarnings: false },
          }),
        },
        numComments: 5,
        recentVisibleComments: [createComment({ createdAt: '2025-06-06T12:00:00Z' })],
      },
    });

    // ASSERT
    expect(screen.getByText(/jun 06, 2025/i)).toBeVisible();
  });

  it('given the user clicks the card, switches to the community tab', async () => {
    // ARRANGE
    render(<CommentsPreviewCard />, {
      jotaiAtoms: [
        [currentTabAtom, 'achievements'],
        //
      ],
      pageProps: {
        numComments: 5,
        recentVisibleComments: [createComment()],
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button'));

    // ASSERT
    expect(router.visit).toHaveBeenCalledWith(
      expect.stringContaining('tab=community'),
      expect.objectContaining({
        preserveScroll: true,
        preserveState: true,
      }),
    );
  });

  it('given the user clicks the card, scrolls to the bottom of the comments section', async () => {
    // ARRANGE
    const mockScrollIntoView = vi.fn();

    const commentsElement = document.createElement('div');
    commentsElement.id = 'comments';
    commentsElement.scrollIntoView = mockScrollIntoView;
    document.body.appendChild(commentsElement);

    render(<CommentsPreviewCard />, {
      pageProps: {
        numComments: 5,
        recentVisibleComments: [createComment()],
        ziggy: createZiggyProps(),
      },
    });

    // ACT
    await userEvent.click(screen.getByRole('button'));

    // ASSERT
    await vi.waitFor(() => {
      expect(mockScrollIntoView).toHaveBeenCalledWith({ behavior: 'smooth', block: 'end' });
    });

    document.body.removeChild(commentsElement);
  });
});
