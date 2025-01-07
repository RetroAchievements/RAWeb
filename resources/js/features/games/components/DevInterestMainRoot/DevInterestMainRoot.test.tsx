import { render, screen } from '@/test';
import { createGame, createSystem, createUser } from '@/test/factories';

import { DevInterestMainRoot } from './DevInterestMainRoot';

describe('Component: DevInterestMainRoot', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.DeveloperInterestPageProps>(
      <DevInterestMainRoot />,
      {
        pageProps: {
          developers: [],
          game: createGame(),
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays game breadcrumbs', () => {
    // ARRANGE
    const system = createSystem({ name: 'Nintendo 64' });
    const game = createGame({ system });

    render<App.Platform.Data.DeveloperInterestPageProps>(<DevInterestMainRoot />, {
      pageProps: {
        game,
        developers: [],
      },
    });

    // ASSERT
    expect(screen.getByRole('listitem', { name: /all games/i })).toBeVisible();
    expect(screen.getByRole('listitem', { name: system.name })).toBeVisible();
    expect(screen.getByRole('listitem', { name: game.title })).toBeVisible();
  });

  it('displays an accessible heading', () => {
    // ARRANGE
    const system = createSystem({ name: 'Nintendo 64' });
    const game = createGame({ system });

    render<App.Platform.Data.DeveloperInterestPageProps>(<DevInterestMainRoot />, {
      pageProps: {
        game,
        developers: [],
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /developer interest/i })).toBeVisible();
  });

  it('given there are no developers interested, displays an empty state message', () => {
    // ARRANGE
    render<App.Platform.Data.DeveloperInterestPageProps>(<DevInterestMainRoot />, {
      pageProps: {
        developers: [],
        game: createGame(),
      },
    });

    // ASSERT
    expect(screen.getByText(/no users have added this game/i));
    expect(screen.queryByRole('table')).not.toBeInTheDocument();
  });

  it('given there are developers interested, shows the list of developers', () => {
    // ARRANGE
    render<App.Platform.Data.DeveloperInterestPageProps>(<DevInterestMainRoot />, {
      pageProps: {
        developers: [
          createUser({ displayName: 'Scott' }),
          createUser({ displayName: 'Jamiras' }),
          createUser({ displayName: 'luchaos' }),
        ],
        game: createGame(),
      },
    });

    // ASSERT
    expect(screen.getByRole('table')).toBeVisible();

    expect(screen.getByRole('link', { name: /scott/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /jamiras/i })).toBeVisible();
    expect(screen.getByRole('link', { name: /luchaos/i })).toBeVisible();
  });
});
