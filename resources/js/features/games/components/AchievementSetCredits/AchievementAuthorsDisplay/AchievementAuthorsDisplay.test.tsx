import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';
import { createGame, createUserCredits } from '@/test/factories';

import { AchievementAuthorsDisplay } from './AchievementAuthorsDisplay';

describe('Component: AchievementAuthorsDisplay', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<AchievementAuthorsDisplay authors={[]} />, {
      pageProps: {
        game: createGame({ achievementsPublished: 100 }),
      },
    });

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given authors with >= 33%, shows them individually with labels', () => {
    // ARRANGE
    const authors = [
      createUserCredits({ displayName: 'Alice', count: 40 }), // !! 40%
      createUserCredits({ displayName: 'Bob', count: 35 }), // !! 35%
      createUserCredits({ displayName: 'Charlie', count: 25 }), // !! 25%
    ];

    render(<AchievementAuthorsDisplay authors={authors} />, {
      pageProps: {
        game: createGame({ achievementsPublished: 100 }),
      },
    });

    // ASSERT
    // ... should show 3 avatar images (2 prominent + 1 in stack) ...
    const avatarImages = screen.getAllByRole('img');
    expect(avatarImages).toHaveLength(3);

    // ... should show 2 separator dots ...
    const separators = screen.getAllByText('•');
    expect(separators).toHaveLength(2);

    // ... Alice and Bob have labels because they have >= 33% contribution ...
    expect(screen.getByText(/alice/i)).toBeVisible();
    expect(screen.getByText(/bob/i)).toBeVisible();

    // ... Charlie is in the stack without a label (< 33% contribution) ...
    expect(screen.queryByText(/charlie/i)).not.toBeInTheDocument();
  });

  it('given one author with >= 33% and others with < 33%, shows prominent author individually and stacks the rest', () => {
    // ARRANGE
    const authors = [
      createUserCredits({ displayName: 'Alice', count: 50 }), // !! 50%
      createUserCredits({ displayName: 'Bob', count: 20 }), // !! 20%
      createUserCredits({ displayName: 'Charlie', count: 15 }), // !! 15%
      createUserCredits({ displayName: 'David', count: 10 }), // !! 10%
      createUserCredits({ displayName: 'Eve', count: 5 }), // !! 5%
    ];

    render(<AchievementAuthorsDisplay authors={authors} />, {
      pageProps: {
        game: createGame({ achievementsPublished: 100 }),
      },
    });

    // ASSERT
    // ... should show 5 avatar images total (1 individual + 4 in stack) ...
    const avatarImages = screen.getAllByRole('img');
    expect(avatarImages).toHaveLength(5);

    // ... should show 1 separator dot ...
    const separators = screen.getAllByText('•');
    expect(separators).toHaveLength(1);

    expect(screen.getByText(/alice/i)).toBeVisible();
  });

  it('given all authors with < 33%, shows all avatars in a single stack', () => {
    // ARRANGE
    const authors = [
      createUserCredits({ displayName: 'Alice', count: 20 }), // !! 20%
      createUserCredits({ displayName: 'Bob', count: 18 }), // !! 18%
      createUserCredits({ displayName: 'Charlie', count: 15 }), // !! 15%
      createUserCredits({ displayName: 'David', count: 15 }), // !! 15%
      createUserCredits({ displayName: 'Eve', count: 12 }), // !! 12%
      createUserCredits({ displayName: 'Frank', count: 10 }), // !! 10%
      createUserCredits({ displayName: 'Grace', count: 10 }), // !! 10%
    ];

    render(<AchievementAuthorsDisplay authors={authors} />, {
      pageProps: {
        game: createGame({ achievementsPublished: 100 }),
      },
    });

    // ASSERT
    // ... should show 7 avatar images in the stack ...
    const avatarImages = screen.getAllByRole('img');
    expect(avatarImages).toHaveLength(7);

    // ... should not show any separator dots ...
    expect(screen.queryByText('•')).not.toBeInTheDocument();
  });

  it('shows authors in the tooltip when hovering the trophy icon', async () => {
    // ARRANGE
    const authors = [
      createUserCredits({ displayName: 'Alice', count: 50 }),
      createUserCredits({ displayName: 'Bob', count: 50 }),
    ];

    render(<AchievementAuthorsDisplay authors={authors} />, {
      pageProps: {
        game: createGame({ achievementsPublished: 100 }),
      },
    });

    // ACT
    await userEvent.hover(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/achievement authors/i)[0]).toBeVisible();
    });
    expect(screen.getAllByText('Alice')[0]).toBeVisible();
    expect(screen.getAllByText('Bob')[0]).toBeVisible();
  });

  it('given multiple authors, shows the correct count in the trophy icon text', () => {
    // ARRANGE
    const authors = [
      createUserCredits({ displayName: 'Alice' }),
      createUserCredits({ displayName: 'Bob' }),
      createUserCredits({ displayName: 'Charlie' }),
    ];

    render(<AchievementAuthorsDisplay authors={authors} />, {
      pageProps: {
        game: createGame({ achievementsPublished: 100 }),
      },
    });

    // ASSERT
    expect(screen.getByText(/3 authors/i)).toBeVisible();
  });

  it('given achievementsPublished is 0 (edge case, this should not happen), renders without crashing', () => {
    // ARRANGE
    const authors = [
      createUserCredits({ displayName: 'Alice', count: 5 }),
      createUserCredits({ displayName: 'Bob', count: 3 }),
      createUserCredits({ displayName: 'Charlie', count: 2 }),
    ];

    const { container } = render(<AchievementAuthorsDisplay authors={authors} />, {
      pageProps: {
        game: createGame({ achievementsPublished: 0 }), // !!
      },
    });

    // ASSERT
    expect(container).toBeTruthy();

    // ... should show 3 avatar images in the stack ...
    const avatarImages = screen.getAllByRole('img');
    expect(avatarImages).toHaveLength(3);

    // ... no authors should have labels (all are in the stack) ...
    expect(screen.queryByText(/alice/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/bob/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/charlie/i)).not.toBeInTheDocument();
  });
});
