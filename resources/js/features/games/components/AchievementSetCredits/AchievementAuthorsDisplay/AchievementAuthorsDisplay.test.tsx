import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';
import { createUserCredits } from '@/test/factories';

import { AchievementAuthorsDisplay } from './AchievementAuthorsDisplay';

describe('Component: AchievementAuthorsDisplay', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(<AchievementAuthorsDisplay authors={[]} />);

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given 1-3 authors, shows individual avatars with separators', () => {
    // ARRANGE
    const authors = [
      createUserCredits({ displayName: 'Alice' }),
      createUserCredits({ displayName: 'Bob' }),
      createUserCredits({ displayName: 'Charlie' }),
    ];

    render(<AchievementAuthorsDisplay authors={authors} />);

    // ASSERT
    // ... should show 3 individual avatar images ...
    const avatarImages = screen.getAllByRole('img');
    expect(avatarImages).toHaveLength(3);

    // ... should show 2 separator dots between the 3 avatars ...
    const separators = screen.getAllByText('•');
    expect(separators).toHaveLength(2);

    expect(screen.getByText(/alice/i)).toBeVisible();
    expect(screen.getByText(/bob/i)).toBeVisible();
    expect(screen.getByText(/charlie/i)).toBeVisible();
  });

  it('given 4-6 authors, shows first 2 avatars individually and the rest in a stack', () => {
    // ARRANGE
    const authors = [
      createUserCredits({ displayName: 'Alice' }),
      createUserCredits({ displayName: 'Bob' }),
      createUserCredits({ displayName: 'Charlie' }),
      createUserCredits({ displayName: 'David' }),
      createUserCredits({ displayName: 'Eve' }),
    ];

    render(<AchievementAuthorsDisplay authors={authors} />);

    // ASSERT
    // ... should show 5 avatar images total (2 individual + 3 in stack) ...
    const avatarImages = screen.getAllByRole('img');
    expect(avatarImages).toHaveLength(5);

    // ... should show 2 separator dots ...
    const separators = screen.getAllByText('•');
    expect(separators).toHaveLength(2);

    expect(screen.getByText(/alice/i)).toBeVisible();
    expect(screen.getByText(/bob/i)).toBeVisible();
  });

  it('given 7+ authors, shows all avatars in a single stack', () => {
    // ARRANGE
    const authors = [
      createUserCredits({ displayName: 'Alice' }),
      createUserCredits({ displayName: 'Bob' }),
      createUserCredits({ displayName: 'Charlie' }),
      createUserCredits({ displayName: 'David' }),
      createUserCredits({ displayName: 'Eve' }),
      createUserCredits({ displayName: 'Frank' }),
      createUserCredits({ displayName: 'Grace' }),
    ];

    render(<AchievementAuthorsDisplay authors={authors} />);

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
      createUserCredits({ displayName: 'Alice', count: 10 }),
      createUserCredits({ displayName: 'Bob', count: 5 }),
    ];

    render(<AchievementAuthorsDisplay authors={authors} />);

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

    render(<AchievementAuthorsDisplay authors={authors} />);

    // ASSERT
    expect(screen.getByText(/3 authors/i)).toBeVisible();
  });
});
