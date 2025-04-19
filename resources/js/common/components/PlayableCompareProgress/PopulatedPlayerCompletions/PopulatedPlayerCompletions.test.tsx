import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';
import { createFollowedPlayerCompletion, createGame, createUser } from '@/test/factories';

import { PopulatedPlayerCompletions } from './PopulatedPlayerCompletions';

describe('Component: PopulatedPlayerCompletions', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const followedPlayerCompletions = [createFollowedPlayerCompletion()];
    const game = createGame();

    const { container } = render(
      <PopulatedPlayerCompletions
        followedPlayerCompletions={followedPlayerCompletions}
        game={game}
      />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given more than 11 users, displays the first 10 and hides the rest', () => {
    // ARRANGE
    const followedPlayerCompletions = Array.from({ length: 15 }, (_, index) =>
      createFollowedPlayerCompletion({ user: createUser({ displayName: `User ${index + 1}` }) }),
    );
    const game = createGame();

    render(
      <PopulatedPlayerCompletions
        followedPlayerCompletions={followedPlayerCompletions}
        game={game}
      />,
    );

    // ASSERT
    // ... only the first 10 should be visible initially ...
    for (let i = 1; i <= 10; i += 1) {
      expect(screen.getByText(`User ${i}`)).toBeVisible();
    }

    // ... users 11-15 should not be visible initially ...
    for (let i = 11; i <= 15; i += 1) {
      expect(screen.queryByText(`User ${i}`)).not.toBeInTheDocument();
    }

    expect(screen.getByText(/see 5 more/i)).toBeVisible();
  });

  it('given exactly 11 users, displays all of them without a "See more" button', () => {
    // ARRANGE
    const followedPlayerCompletions = Array.from({ length: 11 }, (_, index) =>
      createFollowedPlayerCompletion({ user: createUser({ displayName: `User ${index + 1}` }) }),
    );
    const game = createGame();

    render(
      <PopulatedPlayerCompletions
        followedPlayerCompletions={followedPlayerCompletions}
        game={game}
      />,
    );

    // ASSERT
    for (let i = 1; i <= 11; i += 1) {
      expect(screen.getByText(`User ${i}`)).toBeVisible();
    }

    expect(screen.queryByText(/see more/i)).not.toBeInTheDocument();
  });

  it('given the user clicks the "See more" button, expands to show all users', async () => {
    // ARRANGE
    const followedPlayerCompletions = Array.from({ length: 15 }, (_, index) =>
      createFollowedPlayerCompletion({ user: createUser({ displayName: `User ${index + 1}` }) }),
    );
    const game = createGame();

    render(
      <PopulatedPlayerCompletions
        followedPlayerCompletions={followedPlayerCompletions}
        game={game}
      />,
    );

    // ACT
    await userEvent.click(screen.getByRole('button', { name: /see 5 more/i }));

    // ASSERT
    await waitFor(() => {
      for (let i = 1; i <= 15; i += 1) {
        expect(screen.getByText(`User ${i}`)).toBeVisible();
      }
    });
  });
});
