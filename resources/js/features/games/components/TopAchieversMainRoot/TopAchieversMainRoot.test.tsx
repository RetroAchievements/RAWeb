import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import {
  createGame,
  createRankedGameTopAchiever,
  createPaginatedData,
  createSystem,
  createUser,
} from '@/test/factories';

import { TopAchieversMainRoot } from './TopAchieversMainRoot';

describe('Component: TopAchieversMainRoot', () => {
  beforeEach(() => {
    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.GameTopAchieversPageProps>(
      <TopAchieversMainRoot />,
      {
        pageProps: {
          game: createGame(),
          paginatedUsers: createPaginatedData([]),
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

    render<App.Platform.Data.GameTopAchieversPageProps>(<TopAchieversMainRoot />, {
      pageProps: {
        game,
        paginatedUsers: createPaginatedData([]),
      },
    });

    // ASSERT
    expect(screen.getByRole('listitem', { name: /all games/i })).toBeVisible();
    expect(screen.getByRole('listitem', { name: system.name })).toBeVisible();
    expect(screen.getByRole('listitem', { name: game.title })).toBeVisible();
  });

  it('displays an accessible heading', () => {
    // ARRANGE
    render<App.Platform.Data.GameTopAchieversPageProps>(<TopAchieversMainRoot />, {
      pageProps: {
        game: createGame(),
        paginatedUsers: createPaginatedData([]),
      },
    });

    // ASSERT
    expect(screen.getByRole('heading', { name: /top achievers/i })).toBeVisible();
  });

  it('displays pagination controls', () => {
    // ARRANGE
    const paginatedUsers = createPaginatedData([createRankedGameTopAchiever()], {
      currentPage: 1,
      lastPage: 2,
      perPage: 1,
      total: 2,
      unfilteredTotal: 2,
      links: {
        previousPageUrl: '#',
        firstPageUrl: '#',
        lastPageUrl: '#',
        nextPageUrl: '#',
      },
    });

    render<App.Platform.Data.GameTopAchieversPageProps>(<TopAchieversMainRoot />, {
      pageProps: {
        game: createGame(),
        paginatedUsers,
      },
    });

    // ASSERT
    expect(
      screen.getAllByRole('navigation', { name: /pagination/i }).length,
    ).toBeGreaterThanOrEqual(1);
  });

  it('given there are entries, displays them', () => {
    // ARRANGE
    const user1 = createUser();
    const user2 = createUser();
    render<App.Platform.Data.GameTopAchieversPageProps>(<TopAchieversMainRoot />, {
      pageProps: {
        game: createGame(),
        paginatedUsers: createPaginatedData([
          createRankedGameTopAchiever({ user: user1 }),
          createRankedGameTopAchiever({ user: user2 }),
        ]),
      },
    });

    // ASSERT
    expect(screen.getByText(user1.displayName)).toBeVisible();
    expect(screen.getByText(user2.displayName)).toBeVisible();
  });

  it('given the user paginates, changes the current route correctly', async () => {
    // ARRANGE
    const visitSpy = vi.spyOn(router, 'visit').mockImplementationOnce(vi.fn());

    const paginatedUsers = createPaginatedData([createRankedGameTopAchiever()], {
      perPage: 1,
      lastPage: 2,
      currentPage: 1,
      links: {
        previousPageUrl: null,
        firstPageUrl: null,
        nextPageUrl: '#',
        lastPageUrl: '#',
      },
    });

    render<App.Platform.Data.GameTopAchieversPageProps>(<TopAchieversMainRoot />, {
      pageProps: {
        game: createGame({ id: 1 }),
        paginatedUsers,
      },
    });

    // ACT
    const comboboxEl = screen.getAllByRole('combobox')[0];
    await userEvent.selectOptions(comboboxEl, ['2']);

    // ASSERT
    expect(visitSpy).toHaveBeenCalledOnce();
    expect(visitSpy).toHaveBeenCalledWith([
      'game.top-achievers.index',
      { game: 1, _query: { page: 2 } },
    ]);
  });
});
