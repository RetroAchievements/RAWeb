import userEvent from '@testing-library/user-event';

import { render, screen, waitFor } from '@/test';
import { createUserCredits } from '@/test/factories';

import { ArtworkCreditsDisplay } from './ArtworkCreditsDisplay';

describe('Component: ArtworkCreditsDisplay', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <ArtworkCreditsDisplay achievementArtworkCredits={[]} badgeArtworkCredits={[]} />,
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('given badge artwork credits exist, shows them in the tooltip', async () => {
    // ARRANGE
    const badgeArtworkCredits = [
      createUserCredits({ displayName: 'Alice' }),
      createUserCredits({ displayName: 'Bob' }),
    ];

    render(
      <ArtworkCreditsDisplay
        achievementArtworkCredits={[]}
        badgeArtworkCredits={badgeArtworkCredits}
      />,
    );

    // ACT
    await userEvent.hover(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/game badge artwork/i)[0]).toBeVisible();
    });
    expect(screen.getAllByText('Alice')[0]).toBeVisible();
    expect(screen.getAllByText('Bob')[0]).toBeVisible();
  });

  it('given achievement artwork credits exist, shows them in the tooltip', async () => {
    // ARRANGE
    const achievementArtworkCredits = [
      createUserCredits({ displayName: 'Charlie', count: 8 }),
      createUserCredits({ displayName: 'David', count: 12 }),
    ];

    render(
      <ArtworkCreditsDisplay
        achievementArtworkCredits={achievementArtworkCredits}
        badgeArtworkCredits={[]}
      />,
    );

    // ACT
    await userEvent.hover(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/achievement artwork/i)[0]).toBeVisible();
    });
    expect(screen.getAllByText('Charlie')[0]).toBeVisible();
    expect(screen.getAllByText('David')[0]).toBeVisible();
  });

  it('given both credit types have users, shows all sections in the tooltip', async () => {
    // ARRANGE
    const badgeArtworkCredits = [createUserCredits({ displayName: 'Alice' })];
    const achievementArtworkCredits = [createUserCredits({ displayName: 'Bob', count: 10 })];

    render(
      <ArtworkCreditsDisplay
        achievementArtworkCredits={achievementArtworkCredits}
        badgeArtworkCredits={badgeArtworkCredits}
      />,
    );

    // ACT
    await userEvent.hover(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/game badge artwork/i)[0]).toBeVisible();
    });
    expect(screen.getAllByText(/achievement artwork/i)[0]).toBeVisible();
  });

  it('given the same user appears in multiple credit types, only shows them once in the avatar stack', () => {
    // ARRANGE
    const sharedUserData = { displayName: 'Alice' };
    const badgeArtworkCredits = [createUserCredits(sharedUserData)];
    const achievementArtworkCredits = [
      createUserCredits({ ...sharedUserData, count: 15 }),
      createUserCredits({ displayName: 'Bob', count: 5 }),
    ];

    render(
      <ArtworkCreditsDisplay
        achievementArtworkCredits={achievementArtworkCredits}
        badgeArtworkCredits={badgeArtworkCredits}
      />,
    );

    // ASSERT
    // ... should show 2 unique users (Alice, Bob) ...
    const avatarImages = screen.getAllByRole('img');
    expect(avatarImages).toHaveLength(2);
  });

  it('given no credits of a certain type, does not show that section in the tooltip', async () => {
    // ARRANGE
    const badgeArtworkCredits = [createUserCredits({ displayName: 'Alice' })];

    render(
      <ArtworkCreditsDisplay
        achievementArtworkCredits={[]}
        badgeArtworkCredits={badgeArtworkCredits}
      />,
    );

    // ACT
    await userEvent.hover(screen.getByRole('button'));

    // ASSERT
    await waitFor(() => {
      expect(screen.getAllByText(/game badge artwork/i)[0]).toBeVisible();
    });
    expect(screen.queryByText(/achievement artwork/i)).not.toBeInTheDocument();
  });
});
