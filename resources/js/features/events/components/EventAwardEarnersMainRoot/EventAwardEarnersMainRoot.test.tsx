import { router } from '@inertiajs/react';
import userEvent from '@testing-library/user-event';

import { render, screen } from '@/test';
import {
  createAwardEarner,
  createEventAward,
  createGame,
  createPaginatedData,
  createRaEvent,
  createSystem,
  createUser,
} from '@/test/factories';

import { EventAwardEarnersMainRoot } from './EventAwardEarnersMainRoot';

describe('Component: EventAwardEarnersMainRoot', () => {
  beforeEach(() => {
    window.HTMLElement.prototype.hasPointerCapture = vi.fn();
    window.HTMLElement.prototype.scrollIntoView = vi.fn();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { container } = render<App.Platform.Data.EventAwardEarnersPageProps>(
      <EventAwardEarnersMainRoot />,
      {
        pageProps: {
          event: createRaEvent(),
          eventAward: createEventAward(),
          paginatedUsers: createPaginatedData([]),
        },
      },
    );

    // ASSERT
    expect(container).toBeTruthy();
  });

  it('displays event breadcrumbs', () => {
    // ARRANGE
    const system = createSystem({ name: 'Events' });
    const legacyGame = createGame({ system });
    const event = createRaEvent({ legacyGame });
    const eventAward = createEventAward();

    render<App.Platform.Data.EventAwardEarnersPageProps>(<EventAwardEarnersMainRoot />, {
      pageProps: {
        event,
        eventAward,
        paginatedUsers: createPaginatedData([]),
      },
    });

    // ASSERT
    expect(screen.getByRole('listitem', { name: /all events/i })).toBeVisible();
    expect(screen.getByRole('listitem', { name: legacyGame.title })).toBeVisible();
    expect(screen.getByRole('listitem', { name: eventAward.label })).toBeVisible();
  });

  it('displays pagination controls', () => {
    // ARRANGE
    const paginatedUsers = createPaginatedData([createAwardEarner()], {
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

    render<App.Platform.Data.EventAwardEarnersPageProps>(<EventAwardEarnersMainRoot />, {
      pageProps: {
        event: createRaEvent(),
        eventAward: createEventAward(),
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
    render<App.Platform.Data.EventAwardEarnersPageProps>(<EventAwardEarnersMainRoot />, {
      pageProps: {
        event: createRaEvent(),
        eventAward: createEventAward(),
        paginatedUsers: createPaginatedData([
          createAwardEarner({ user: user1 }),
          createAwardEarner({ user: user2 }),
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

    const event = createRaEvent();
    const eventAward = createEventAward({ tierIndex: 2 });

    const paginatedUsers = createPaginatedData([createAwardEarner()], {
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

    render<App.Platform.Data.EventAwardEarnersPageProps>(<EventAwardEarnersMainRoot />, {
      pageProps: {
        event,
        eventAward,
        paginatedUsers,
      },
    });

    // ACT
    const comboboxEl = screen.getAllByRole('combobox')[0];
    await userEvent.selectOptions(comboboxEl, ['2']);

    // ASSERT
    expect(visitSpy).toHaveBeenCalledOnce();
    expect(visitSpy).toHaveBeenCalledWith([
      'event.award-earners.index',
      { event: event.id, tier: eventAward.tierIndex, _query: { page: 2 } },
    ]);
  });

  it('shows the correct earners message', () => {
    // ARRANGE
    const event = createRaEvent();
    const eventAward = createEventAward({ badgeCount: 5 });

    render<App.Platform.Data.EventAwardEarnersPageProps>(<EventAwardEarnersMainRoot />, {
      pageProps: {
        event,
        eventAward,
        paginatedUsers: createPaginatedData([]),
      },
    });

    // ASSERT
    expect(screen.getAllByText(/5 players have earned this/i)[1]).toBeVisible();
  });
});
