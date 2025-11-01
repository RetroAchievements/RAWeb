import userEvent from '@testing-library/user-event';

import { createAuthenticatedUser } from '@/common/models';
import { render, screen, waitFor } from '@/test';
import { createGame, createZiggyProps } from '@/test/factories';

import { SidebarContributeLinks } from './SidebarContributeLinks';

describe('Component: SidebarContributeLinks', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render(
      <SidebarContributeLinks canShowDevelopmentAndSubscribe={false} canShowManagement={false} />,
      {
        pageProps: {
          achievementSetClaims: [],
          auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
          backingGame: createGame(),
          can: {},
          game: createGame({ gameAchievementSets: [] }),
          isOnWantToDevList: false,
          isSubscribedToAchievementComments: false,
          isSubscribedToTickets: false,
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays the Contribute button', () => {
    // ARRANGE
    render(
      <SidebarContributeLinks canShowDevelopmentAndSubscribe={true} canShowManagement={false} />,
      {
        pageProps: {
          achievementSetClaims: [],
          auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
          backingGame: createGame(),
          can: {},
          game: createGame({ gameAchievementSets: [] }),
          isOnWantToDevList: false,
          isSubscribedToAchievementComments: false,
          isSubscribedToTickets: false,
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.getByRole('button', { name: 'Contribute' })).toBeVisible();
  });

  it('initially renders in a collapsed state', () => {
    // ARRANGE
    render(
      <SidebarContributeLinks canShowDevelopmentAndSubscribe={true} canShowManagement={true} />,
      {
        pageProps: {
          achievementSetClaims: [],
          auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
          backingGame: createGame(),
          can: { manageGames: true },
          game: createGame({ gameAchievementSets: [] }),
          isOnWantToDevList: false,
          isSubscribedToAchievementComments: false,
          isSubscribedToTickets: false,
          ziggy: createZiggyProps(),
        },
      },
    );

    // ASSERT
    expect(screen.getByRole('button', { name: 'Contribute' })).toBeVisible();
    expect(screen.queryByText(/management/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/development/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/subscribe/i)).not.toBeInTheDocument();
  });

  it('given the user clicks the Contribute button, expands to show child sections', async () => {
    // ARRANGE
    const user = userEvent.setup();

    render(
      <SidebarContributeLinks
        canShowDevelopmentAndSubscribe={true} // !!
        canShowManagement={true} // !!
      />,
      {
        pageProps: {
          achievementSetClaims: [],
          auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
          backingGame: createGame(),
          can: { manageGames: true }, // !!
          game: createGame({ gameAchievementSets: [] }),
          isOnWantToDevList: false,
          isSubscribedToAchievementComments: false,
          isSubscribedToTickets: false,
          ziggy: createZiggyProps(),
        },
      },
    );

    // ACT
    await user.click(screen.getByRole('button', { name: 'Contribute' }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/management/i)).toBeVisible();
    });
    expect(screen.getByText(/development/i)).toBeVisible();
    expect(screen.getByText(/subscribe/i)).toBeVisible();
  });

  it('given canShowManagement is true and canShowDevelopmentAndSubscribe is false, only renders the Management section when expanded', async () => {
    // ARRANGE
    const user = userEvent.setup();

    render(
      <SidebarContributeLinks
        canShowDevelopmentAndSubscribe={false} // !!
        canShowManagement={true} // !!
      />,
      {
        pageProps: {
          achievementSetClaims: [],
          auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
          backingGame: createGame(),
          can: { manageGames: true }, // !!
          game: createGame({ gameAchievementSets: [] }),
          isOnWantToDevList: false,
          isSubscribedToAchievementComments: false,
          isSubscribedToTickets: false,
          ziggy: createZiggyProps(),
        },
      },
    );

    // ACT
    await user.click(screen.getByRole('button', { name: 'Contribute' }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/management/i)).toBeVisible();
    });

    expect(screen.queryByText(/development/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/subscribe/i)).not.toBeInTheDocument();
  });

  it('given canShowDevelopmentAndSubscribe is true and canShowManagement is false, only renders Development and Subscribe sections when expanded', async () => {
    // ARRANGE
    const user = userEvent.setup();

    render(
      <SidebarContributeLinks
        canShowDevelopmentAndSubscribe={true} // !!
        canShowManagement={false} // !!
      />,
      {
        pageProps: {
          achievementSetClaims: [],
          auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
          backingGame: createGame(),
          can: {},
          game: createGame({ gameAchievementSets: [] }),
          isOnWantToDevList: false,
          isSubscribedToAchievementComments: false,
          isSubscribedToTickets: false,
          ziggy: createZiggyProps(),
        },
      },
    );

    // ACT
    await user.click(screen.getByRole('button', { name: 'Contribute' }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/development/i)).toBeVisible();
    });
    expect(screen.getByText(/subscribe/i)).toBeVisible();

    expect(screen.queryByText(/management/i)).not.toBeInTheDocument();
  });

  it('given both props are true, renders all three child sections when expanded', async () => {
    // ARRANGE
    const user = userEvent.setup();

    render(
      <SidebarContributeLinks
        canShowDevelopmentAndSubscribe={true} // !!
        canShowManagement={true} // !!
      />,
      {
        pageProps: {
          achievementSetClaims: [],
          auth: { user: createAuthenticatedUser({ roles: ['developer'] }) },
          backingGame: createGame(),
          can: { manageGames: true }, // !!
          game: createGame({ gameAchievementSets: [] }),
          isOnWantToDevList: false,
          isSubscribedToAchievementComments: false,
          isSubscribedToTickets: false,
          ziggy: createZiggyProps(),
        },
      },
    );

    // ACT
    await user.click(screen.getByRole('button', { name: 'Contribute' }));

    // ASSERT
    await waitFor(() => {
      expect(screen.getByText(/management/i)).toBeVisible();
    });
    expect(screen.getByText(/development/i)).toBeVisible();
    expect(screen.getByText(/subscribe/i)).toBeVisible();
  });
});
